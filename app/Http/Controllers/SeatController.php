<?php

namespace App\Http\Controllers;

use App\Events\SeatStatusChanged;
use App\Http\Requests\Seat\LockSeatsRequest;
use App\Http\Resources\SeatResource;
use App\Models\BookingSeat;
use App\Models\Seat;
use App\Models\SeatLock;
use App\Models\Showtime;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class SeatController extends Controller
{
    #[OA\Get(
        path: '/api/showtimes/{showtime}/seats',
        summary: 'Get seat map for a showtime',
        description: 'Returns all seats with status: available, locked, or booked. is_mine=true if the logged-in user holds the lock.',
        tags: ['Seats'],
        parameters: [new OA\Parameter(name: 'showtime', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Seats retrieved')]
    )]
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

    #[OA\Post(
        path: '/api/showtimes/{showtime}/seats/lock',
        summary: 'Lock seats for 10 minutes',
        description: 'First-come-first-serve. Returns 409 if another user already locked the seat.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'seat_ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2]),
        ])),
        tags: ['Seats'],
        parameters: [new OA\Parameter(name: 'showtime', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Seats locked'),
            new OA\Response(response: 409, description: 'Seat already taken'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
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

        // Tell all connected clients these seats are now locked
        $seats = Seat::whereIn('id', $seatIds)->get()->keyBy('id');
        foreach ($seatIds as $seatId) {
            $seat = $seats[$seatId];
            SeatStatusChanged::dispatch($showtime->id, $seatId, $seat->row, $seat->number, 'locked');
        }

        return response()->json([
            'data' => [
                'seat_ids' => $seatIds,
                'expires_at' => $expiresAt,
            ],
            'message' => 'Seats locked for 10 minutes',
        ]);
    }

    #[OA\Delete(
        path: '/api/showtimes/{showtime}/seats/lock',
        summary: 'Release your seat locks',
        security: [['bearerAuth' => []]],
        tags: ['Seats'],
        parameters: [new OA\Parameter(name: 'showtime', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Seats released'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function unlock(Showtime $showtime): JsonResponse
    {
        $locks = SeatLock::where('showtime_id', $showtime->id)
            ->where('user_id', auth()->id())
            ->with('seat')
            ->get();

        SeatLock::where('showtime_id', $showtime->id)
            ->where('user_id', auth()->id())
            ->delete();

        foreach ($locks as $lock) {
            SeatStatusChanged::dispatch(
                $lock->showtime_id,
                $lock->seat_id,
                $lock->seat->row,
                $lock->seat->number,
                'available'
            );
        }

        return response()->json(['message' => 'Seats released']);
    }
}
