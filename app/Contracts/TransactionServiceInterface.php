<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\Payment;

interface TransactionServiceInterface
{
    public function recordTransaction(Order $order, string $stripePaymentIntentId, float $amount): Payment;

    public function updateTransactionStatus(Payment $payment, string $status, array $metadata = []): void;

    public function markAsSucceeded(Payment $payment, string $paymentMethod, ?string $last4): void;

    public function markAsFailed(Payment $payment): void;
}
