<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Pagos',
    description: 'Endpoints para procesamiento de pagos con Stripe'
)]
class PaymentController extends Controller
{
    private StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
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
        if ($order->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Esta orden no puede ser pagada.',
                'status' => $order->status,
            ], 422);
        }

        if ($order->payment && $order->payment->status === 'succeeded') {
            return response()->json([
                'message' => 'Esta orden ya fue pagada.',
            ], 422);
        }

        try {
            $paymentIntent = $this->stripeService->createPaymentIntent($order);

            return response()->json([
                'message' => 'PaymentIntent creado exitosamente.',
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $order->total,
                'currency' => config('stripe.currency', 'usd'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar el pago.',
                'error' => $e->getMessage(),
            ], 500);
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
            $paymentIntent = $this->stripeService->confirmPayment($request->payment_intent_id);

            $payment = \App\Models\Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

            if (!$payment) {
                return response()->json([
                    'message' => 'Pago no encontrado.',
                ], 404);
            }

            if ($payment->order->user_id !== auth()->id()) {
                return response()->json(['message' => 'No autorizado.'], 403);
            }

            $this->stripeService->updatePaymentStatus($payment, $paymentIntent);

            $payment->refresh();

            return response()->json([
                'message' => 'Pago procesado exitosamente.',
                'payment' => $payment,
                'order' => $payment->order,
                'status' => $paymentIntent->status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al confirmar el pago.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
