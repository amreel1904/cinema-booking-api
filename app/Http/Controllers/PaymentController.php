<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\SubmitPaymentRequest;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class PaymentController extends Controller
{
    #[OA\Get(
        path: '/api/payment-methods',
        summary: 'List available payment methods',
        tags: ['Payment'],
        responses: [new OA\Response(response: 200, description: 'Payment methods retrieved')]
    )]
    public function methods(): JsonResponse
    {
        return response()->json([
            'data' => [
                ['method' => 'credit_card', 'label' => 'Credit Card'],
                ['method' => 'debit_card', 'label' => 'Debit Card'],
                ['method' => 'online_banking', 'label' => 'Online Banking'],
                ['method' => 'ewallet', 'label' => 'E-Wallet'],
            ],
            'message' => 'Payment methods retrieved',
        ]);
    }

    #[OA\Post(
        path: '/api/bookings/{booking}/payment',
        summary: 'Pay for a booking (mock)',
        description: 'Immediately confirms payment. No real gateway — demo only.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'method', type: 'string', enum: ['credit_card', 'debit_card', 'online_banking', 'ewallet'], example: 'credit_card'),
        ])),
        tags: ['Payment'],
        parameters: [new OA\Parameter(name: 'booking', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Payment confirmed'),
            new OA\Response(response: 422, description: 'Booking already paid'),
            new OA\Response(response: 404, description: 'Booking not found or not yours'),
        ]
    )]
    public function store(SubmitPaymentRequest $request, string $booking): JsonResponse
    {
        $record = Booking::where('id', $booking)
            ->where('user_id', auth()->id())
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($record->status !== 'pending') {
            return response()->json(['message' => 'Booking has already been paid'], 422);
        }

        $payment = DB::transaction(function () use ($request, $record) {
            $payment = Payment::create([
                'booking_id' => $record->id,
                'method' => $request->method,
                'status' => 'confirmed',
                'amount' => $record->total_amount,
            ]);

            $record->update(['status' => 'confirmed']);

            return $payment;
        });

        return response()->json([
            'data' => [
                'booking_id' => $record->id,
                'status' => $payment->status,
                'method' => $payment->method,
                'amount' => $payment->amount,
            ],
            'message' => 'Payment confirmed',
        ]);
    }
}
