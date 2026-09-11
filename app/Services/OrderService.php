<?php

namespace App\Services;

use App\Contracts\OrderServiceInterface;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService implements OrderServiceInterface
{
    public function getUserOrders(User $user)
    {
        return $user->orders()
            ->with(['items.product', 'payment'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getOrderForUser(int $orderId, User $user): ?Order
    {
        $order = Order::with('items.product', 'payment')->find($orderId);

        if (!$order || $order->user_id !== $user->id) {
            return null;
        }

        return $order;
    }

    public function createOrder(array $data, User $user): Order
    {
        return DB::transaction(function () use ($data, $user) {
            $total = 0;
            $itemsData = [];

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Stock insuficiente para el producto: {$product->name}");
                }

                $subtotal = $product->price * $item['quantity'];
                $total += $subtotal;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'subtotal' => $subtotal,
                ];

                $product->decrement('stock', $item['quantity']);
            }

            $order = Order::create([
                'user_id' => $user->id,
                'total' => $total,
                'status' => 'pending',
                'shipping_address' => $data['shipping_address'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($itemsData as $itemData) {
                $order->items()->create($itemData);
            }

            return $order->load('items.product');
        });
    }
}
