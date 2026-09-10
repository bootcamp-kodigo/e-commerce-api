<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    private StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

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
