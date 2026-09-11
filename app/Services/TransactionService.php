<?php

namespace App\Services;

use App\Contracts\TransactionServiceInterface;
use App\Models\Order;
use App\Models\Payment;

class TransactionService implements TransactionServiceInterface
{
    public function recordTransaction(Order $order, string $stripePaymentIntentId, float $amount): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'stripe_payment_intent_id' => $stripePaymentIntentId,
            'amount' => $amount,
            'currency' => config('stripe.currency', 'usd'),
            'status' => 'pending',
            'metadata' => ['payment_intent_id' => $stripePaymentIntentId],
        ]);
    }

    public function updateTransactionStatus(Payment $payment, string $status, array $metadata = []): void
    {
        $payment->update([
            'status' => $status,
            'metadata' => array_merge($payment->metadata ?? [], $metadata),
        ]);
    }

    public function markAsSucceeded(Payment $payment, string $paymentMethod, ?string $last4): void
    {
        $payment->update([
            'status' => 'succeeded',
            'payment_method' => $paymentMethod,
            'last4' => $last4,
        ]);

        $payment->order->update(['status' => 'paid']);
    }

    public function markAsFailed(Payment $payment): void
    {
        $payment->update([
            'status' => 'failed',
        ]);

        $payment->order->update(['status' => 'failed']);
    }
}
