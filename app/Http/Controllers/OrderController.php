<?php

namespace App\Http\Controllers;

use App\Contracts\OrderServiceInterface;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Órdenes',
    description: 'Endpoints para gestión de órdenes de compra'
)]
class OrderController extends Controller
{
    private OrderServiceInterface $orderService;

    public function __construct(OrderServiceInterface $orderService)
    {
        $this->orderService = $orderService;
    }

    #[OA\Get(
        path: '/orders',
        summary: 'Listar órdenes del usuario',
        description: 'Retorna una lista paginada de órdenes del usuario autenticado',
        operationId: 'getOrders',
        tags: ['Órdenes'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Número de página',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de órdenes',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Order')
                        ),
                        new OA\Property(property: 'total', type: 'integer', example: 1),
                        new OA\Property(property: 'per_page', type: 'integer', example: 15),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado'
            ),
        ]
    )]
    public function index(): JsonResponse
    {
        $orders = $this->orderService->getUserOrders(auth()->user());

        return response()->json($orders);
    }

    #[OA\Get(
        path: '/orders/{order}',
        summary: 'Obtener orden',
        description: 'Retorna los detalles de una orden específica',
        operationId: 'getOrder',
        tags: ['Órdenes'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'order',
                in: 'path',
                required: true,
                description: 'ID de la orden',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalles de la orden',
                content: new OA\JsonContent(ref: '#/components/schemas/Order')
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado'
            ),
            new OA\Response(
                response: 403,
                description: 'No autorizado'
            ),
            new OA\Response(
                response: 404,
                description: 'Orden no encontrada'
            ),
        ]
    )]
    public function show(Order $order): JsonResponse
    {
        $userOrder = $this->orderService->getOrderForUser($order->id, auth()->user());

        if (!$userOrder) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return response()->json($userOrder);
    }

    #[OA\Post(
        path: '/orders',
        summary: 'Crear orden',
        description: 'Crea una nueva orden de compra',
        operationId: 'createOrder',
        tags: ['Órdenes'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['items', 'shipping_address'],
                properties: [
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'product_id', type: 'integer', example: 1),
                                new OA\Property(property: 'quantity', type: 'integer', example: 2),
                            ]
                        )
                    ),
                    new OA\Property(property: 'shipping_address', type: 'string', example: 'Calle Principal 123, Ciudad'),
                    new OA\Property(property: 'notes', type: 'string', example: 'Entregar en horario de oficina'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Orden creada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Orden creada exitosamente.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Order'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado'
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación o stock insuficiente'
            ),
        ]
    )]
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder($request->validated(), auth()->user());

        return response()->json([
            'message' => 'Orden creada exitosamente.',
            'data' => $order,
        ], 201);
    }
}
