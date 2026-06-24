<?php

namespace App\Http\Controllers;

use App\Http\Requests\Seat\LockSeatsRequest;
use App\Http\Resources\SeatResource;
use App\Models\BookingSeat;
use App\Models\Seat;
use App\Models\SeatLock;
use App\Models\Showtime;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SeatController extends Controller
{
    public function index(Showtime $showtime): JsonResponse
    {
        $seats = Seat::where('hall_id', $showtime->hall_id)->get();

        $activeLocks = SeatLock::where('showtime_id', $showtime->id)
            ->where('expires_at', '>', now())
            ->get()
            ->keyBy('seat_id');

        $bookedSeatIds = BookingSeat::whereHas('booking', function ($q) use ($showtime) {
            $q->where('showtime_id', $showtime->id)->where('status', 'confirmed');
        })->pluck('seat_id')->toArray();

        $userId = auth('sanctum')->id();

        $seats->each(function ($seat) use ($activeLocks, $bookedSeatIds, $userId) {
            if (in_array($seat->id, $bookedSeatIds)) {
                $seat->status = 'booked';
                $seat->is_mine = false;
            } elseif ($activeLocks->has($seat->id)) {
                $seat->status = 'locked';
                $seat->is_mine = $activeLocks[$seat->id]->user_id === $userId;
            } else {
                $seat->status = 'available';
                $seat->is_mine = false;
            }
        });

        return response()->json([
            'data' => SeatResource::collection($seats),
            'message' => 'Seats retrieved',
        ]);
    }

    public function lock(LockSeatsRequest $request, Showtime $showtime): JsonResponse
    {
        $seatIds = $request->seat_ids;
        $userId = auth()->id();

        $validCount = Seat::where('hall_id', $showtime->hall_id)
            ->whereIn('id', $seatIds)
            ->count();

        if ($validCount !== count($seatIds)) {
            return response()->json(['message' => 'One or more seats do not belong to this showtime'], 422);
        }

        $alreadyLocked = SeatLock::where('showtime_id', $showtime->id)
            ->whereIn('seat_id', $seatIds)
            ->where('expires_at', '>', now())
            ->exists();

        if ($alreadyLocked) {
            return response()->json(['message' => 'One or more seats are already locked'], 409);
        }

        $expiresAt = now()->addMinutes(10);

        try {
            DB::transaction(function () use ($showtime, $seatIds, $userId, $expiresAt) {
                foreach ($seatIds as $seatId) {
                    SeatLock::create([
                        'showtime_id' => $showtime->id,
                        'seat_id' => $seatId,
                        'user_id' => $userId,
                        'expires_at' => $expiresAt,
                    ]);
                }
            });
        } catch (UniqueConstraintViolationException) {
            return response()->json(['message' => 'One or more seats were just taken'], 409);
        }

        return response()->json([
            'data' => [
                'seat_ids' => $seatIds,
                'expires_at' => $expiresAt,
            ],
            'message' => 'Seats locked for 10 minutes',
        ]);
    }

    public function unlock(Showtime $showtime): JsonResponse
    {
        SeatLock::where('showtime_id', $showtime->id)
            ->where('user_id', auth()->id())
            ->delete();

        return response()->json(['message' => 'Seats released']);
    }
}
