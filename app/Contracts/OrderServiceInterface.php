<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\User;

interface OrderServiceInterface
{
    public function getUserOrders(User $user, int $perPage = 15);

    public function getOrderForUser(int $orderId, User $user): ?Order;

    public function createOrder(array $data, User $user): Order;
}
