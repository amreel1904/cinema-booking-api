<?php

namespace App\Http\Controllers;

use App\Events\SeatStatusChanged;
use App\Http\Requests\Booking\CreateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\BookingFnb;
use App\Models\BookingSeat;
use App\Models\FoodBeverage;
use App\Models\Seat;
use App\Models\SeatLock;
use App\Models\Showtime;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function store(CreateBookingRequest $request): JsonResponse
    {
        $userId = auth()->id();
        $showtime = Showtime::findOrFail($request->showtime_id);
        $seatIds = $request->seat_ids;

        $lockedCount = SeatLock::where('showtime_id', $showtime->id)
            ->where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->whereIn('seat_id', $seatIds)
            ->count();

        if ($lockedCount !== count($seatIds)) {
            return response()->json([
                'message' => 'Seat locks have expired or do not belong to you',
            ], 422);
        }

        $booking = DB::transaction(function () use ($request, $showtime, $seatIds, $userId) {
            $fnbTotal = 0;
            $fnbItems = [];

            foreach ($request->fnb ?? [] as $item) {
                $fnb = FoodBeverage::find($item['id']);
                $fnbTotal += $fnb->price * $item['quantity'];
                $fnbItems[] = ['model' => $fnb, 'quantity' => $item['quantity']];
            }

            $booking = Booking::create([
                'user_id' => $userId,
                'showtime_id' => $showtime->id,
                'status' => 'pending',
                'total_amount' => round(($showtime->price * count($seatIds)) + $fnbTotal, 2),
            ]);

            foreach ($seatIds as $seatId) {
                BookingSeat::create([
                    'booking_id' => $booking->id,
                    'seat_id' => $seatId,
                    'price' => $showtime->price,
                ]);
            }

            foreach ($fnbItems as $item) {
                BookingFnb::create([
                    'booking_id' => $booking->id,
                    'food_beverage_id' => $item['model']->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['model']->price,
                ]);
            }

            SeatLock::where('showtime_id', $showtime->id)
                ->where('user_id', $userId)
                ->whereIn('seat_id', $seatIds)
                ->delete();

            return $booking;
        });

        // Tell all connected clients these seats are now permanently booked
        $seats = Seat::whereIn('id', $seatIds)->get()->keyBy('id');
        foreach ($seatIds as $seatId) {
            $seat = $seats[$seatId];
            SeatStatusChanged::dispatch($showtime->id, $seatId, $seat->row, $seat->number, 'booked');
        }

        return response()->json([
            'data' => new BookingResource(
                $booking->load(['showtime.movie', 'seats.seat', 'fnbs.foodBeverage'])
            ),
            'message' => 'Booking created',
        ], 201);
    }

    public function show(string $booking): JsonResponse
    {
        $record = Booking::where('id', $booking)
            ->where('user_id', auth()->id())
            ->with(['showtime.movie', 'seats.seat', 'fnbs.foodBeverage', 'payment'])
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json([
            'data' => new BookingResource($record),
            'message' => 'Booking retrieved',
        ]);
    }
}
