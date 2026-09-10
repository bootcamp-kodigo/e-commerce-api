<?php

namespace App\Http\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan@example.com'),
        new OA\Property(property: 'phone', type: 'string', example: '+1234567890'),
        new OA\Property(property: 'role', type: 'string', enum: ['customer', 'admin'], example: 'customer'),
        new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Product',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'sku', type: 'string', example: 'LAPTOP-001'),
        new OA\Property(property: 'name', type: 'string', example: 'Laptop Gaming Pro'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 1299.99),
        new OA\Property(property: 'stock', type: 'integer', example: 25),
        new OA\Property(property: 'image_url', type: 'string', format: 'url', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'OrderItem',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'order_id', type: 'integer', example: 1),
        new OA\Property(property: 'product_id', type: 'integer', example: 1),
        new OA\Property(property: 'quantity', type: 'integer', example: 2),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 1299.99),
        new OA\Property(property: 'subtotal', type: 'number', format: 'float', example: 2599.98),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(
            property: 'product',
            ref: '#/components/schemas/Product'
        ),
    ]
)]
#[OA\Schema(
    schema: 'Payment',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'order_id', type: 'integer', example: 1),
        new OA\Property(property: 'stripe_payment_intent_id', type: 'string', example: 'pi_xxx'),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 2849.97),
        new OA\Property(property: 'currency', type: 'string', example: 'usd'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'succeeded', 'failed', 'refunded'], example: 'pending'),
        new OA\Property(property: 'payment_method', type: 'string', nullable: true, example: 'card'),
        new OA\Property(property: 'last4', type: 'string', nullable: true, example: '4242'),
        new OA\Property(property: 'metadata', type: 'object', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Order',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'total', type: 'number', format: 'float', example: 2849.97),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'paid', 'cancelled', 'failed'], example: 'pending'),
        new OA\Property(property: 'shipping_address', type: 'string', example: 'Calle Principal 123, Ciudad'),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/OrderItem')
        ),
        new OA\Property(
            property: 'payment',
            ref: '#/components/schemas/Payment',
            nullable: true
        ),
    ]
)]
class Schemas
{
}
