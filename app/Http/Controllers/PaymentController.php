<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Pagos',
    description: 'Endpoints para procesamiento de pagos con Stripe'
)]
class PaymentController extends Controller
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    #[OA\Post(
        path: '/payments/create-intent/{order}',
        summary: 'Crear PaymentIntent',
        description: 'Crea un PaymentIntent de Stripe para una orden específica',
        operationId: 'createPaymentIntent',
        tags: ['Pagos'],
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
                description: 'PaymentIntent creado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'PaymentIntent creado exitosamente.'),
                        new OA\Property(property: 'client_secret', type: 'string', example: 'pi_xxx_secret_xxx'),
                        new OA\Property(property: 'payment_intent_id', type: 'string', example: 'pi_xxx'),
                        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 2849.97),
                        new OA\Property(property: 'currency', type: 'string', example: 'usd'),
                    ]
                )
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
                response: 422,
                description: 'Orden no puede ser pagada'
            ),
            new OA\Response(
                response: 500,
                description: 'Error al procesar el pago'
            ),
        ]
    )]
    public function createPaymentIntent(Order $order): JsonResponse
    {
        try {
            $result = $this->paymentService->createPaymentIntentForOrder($order, auth()->user());

            return response()->json([
                'message' => 'PaymentIntent creado exitosamente.',
                ...$result,
            ]);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 500 ? $e->getCode() : 500;

            return response()->json([
                'message' => 'Error al procesar el pago.',
                'error' => $e->getMessage(),
            ], $statusCode);
        }
    }

    #[OA\Post(
        path: '/payments/confirm',
        summary: 'Confirmar pago',
        description: 'Confirma el estado de un pago en Stripe',
        operationId: 'confirmPayment',
        tags: ['Pagos'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['payment_intent_id'],
                properties: [
                    new OA\Property(property: 'payment_intent_id', type: 'string', example: 'pi_xxx'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pago procesado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Pago procesado exitosamente.'),
                        new OA\Property(property: 'payment', ref: '#/components/schemas/Payment'),
                        new OA\Property(property: 'order', ref: '#/components/schemas/Order'),
                        new OA\Property(property: 'status', type: 'string', example: 'succeeded'),
                    ]
                )
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
                description: 'Pago no encontrado'
            ),
            new OA\Response(
                response: 500,
                description: 'Error al confirmar el pago'
            ),
        ]
    )]
    public function confirmPayment(Request $request): JsonResponse
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        try {
            $result = $this->paymentService->confirmPaymentForUser(
                $request->payment_intent_id,
                auth()->user()
            );

            return response()->json([
                'message' => 'Pago procesado exitosamente.',
                ...$result,
            ]);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 500 ? $e->getCode() : 500;

            return response()->json([
                'message' => 'Error al confirmar el pago.',
                'error' => $e->getMessage(),
            ], $statusCode);
        }
    }
}
