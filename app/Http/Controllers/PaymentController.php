<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\SubmitPaymentRequest;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
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
