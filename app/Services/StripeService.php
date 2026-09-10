<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Exception\ApiErrorException;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('stripe.secret'));
    }

    public function createPaymentIntent(Order $order): PaymentIntent
    {
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $this->convertToCents($order->total),
                'currency' => config('stripe.currency', 'usd'),
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            Payment::create([
                'order_id' => $order->id,
                'stripe_payment_intent_id' => $paymentIntent->id,
                'amount' => $order->total,
                'currency' => config('stripe.currency', 'usd'),
                'status' => 'pending',
                'metadata' => ['payment_intent_id' => $paymentIntent->id],
            ]);

            return $paymentIntent;
        } catch (ApiErrorException $e) {
            throw new \Exception('Error al crear el pago: ' . $e->getMessage());
        }
    }

    public function confirmPayment(string $paymentIntentId): PaymentIntent
    {
        try {
            return PaymentIntent::retrieve($paymentIntentId);
        } catch (ApiErrorException $e) {
            throw new \Exception('Error al confirmar el pago: ' . $e->getMessage());
        }
    }

    public function updatePaymentStatus(Payment $payment, PaymentIntent $paymentIntent): void
    {
        $status = match($paymentIntent->status) {
            'succeeded' => 'succeeded',
            'requires_payment_method', 'requires_confirmation', 'requires_action' => 'pending',
            'canceled' => 'failed',
            default => 'pending',
        };

        $payment->update([
            'status' => $status,
            'payment_method' => $paymentIntent->payment_method_types[0] ?? null,
            'last4' => $paymentIntent->charges->data[0]->payment_method_details->card->last4 ?? null,
            'metadata' => array_merge($payment->metadata ?? [], [
                'stripe_status' => $paymentIntent->status,
            ]),
        ]);

        if ($status === 'succeeded') {
            $payment->order->update(['status' => 'paid']);
        } elseif ($status === 'failed') {
            $payment->order->update(['status' => 'failed']);
        }
    }

    private function convertToCents(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
