<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\Payment;

interface PaymentServiceInterface
{
    public function createPayment(Order $order): array;

    public function getPaymentStatus(string $paymentId): array;

    public function canProcessPayment(Order $order, ?Payment $existingPayment = null): bool;

    public function extractPaymentDetails(array $paymentData): array;
}
