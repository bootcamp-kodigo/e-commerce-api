<?php

namespace App\Services;

use App\Contracts\PaymentServiceInterface;
use App\Contracts\TransactionServiceInterface;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;

class PaymentService
{
    private PaymentServiceInterface $paymentGateway;
    private TransactionServiceInterface $transactionService;

    public function __construct(
        PaymentServiceInterface $paymentGateway,
        TransactionServiceInterface $transactionService
    ) {
        $this->paymentGateway = $paymentGateway;
        $this->transactionService = $transactionService;
    }

    public function createPaymentIntentForOrder(Order $order, User $user): array
    {
        if ($order->user_id !== $user->id) {
            throw new \Exception('No autorizado', 403);
        }

        if (!$this->paymentGateway->canProcessPayment($order, $order->payment)) {
            throw new \Exception('Esta orden no puede ser pagada', 422);
        }

        $paymentData = $this->paymentGateway->createPayment($order);

        $this->transactionService->recordTransaction($order, $paymentData['payment_id'], $order->total);

        return [
            'client_secret' => $paymentData['client_secret'],
            'payment_intent_id' => $paymentData['payment_id'],
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'],
        ];
    }

    public function confirmPaymentForUser(string $paymentId, User $user): array
    {
        $paymentData = $this->paymentGateway->getPaymentStatus($paymentId);

        $payment = Payment::where('stripe_payment_intent_id', $paymentData['payment_id'])->first();

        if (!$payment) {
            throw new \Exception('Pago no encontrado', 404);
        }

        if ($payment->order->user_id !== $user->id) {
            throw new \Exception('No autorizado', 403);
        }

        $details = $this->paymentGateway->extractPaymentDetails($paymentData);

        $this->updateTransactionFromPaymentData($payment, $details);

        $payment->refresh();

        return [
            'payment' => $payment,
            'order' => $payment->order,
            'status' => $paymentData['status'],
        ];
    }

    private function updateTransactionFromPaymentData(Payment $payment, array $details): void
    {
        $status = $details['status'];

        $this->transactionService->updateTransactionStatus($payment, $status, [
            'stripe_status' => $details['stripe_status'],
        ]);

        if ($status === 'succeeded') {
            $this->transactionService->markAsSucceeded($payment, $details['payment_method'], $details['last4']);
        } elseif ($status === 'failed') {
            $this->transactionService->markAsFailed($payment);
        }
    }
}
