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
        description: 'Returns all seats with status: available, locked, or booked. is_mine=true if the logged-in user holds the lock. Auth is optional — add token to see is_mine.',
        security: [['bearerAuth' => []]],
        tags: ['Seats'],
        parameters: [new OA\Parameter(name: 'showtime', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Seats retrieved')]
    )]
    // Returns all seats for a showtime with their current status.
    // Each seat can be: available, locked (someone is holding it), or booked (payment confirmed).
    // is_mine = true means the currently logged-in user is the one holding the lock.
    public function index(Showtime $showtime): JsonResponse
    {
        // Get all seats in the hall linked to this showtime
        $seats = Seat::where('hall_id', $showtime->hall_id)->get();

        // Get all active locks for this showtime (not expired yet), indexed by seat_id for fast lookup
        $activeLocks = SeatLock::where('showtime_id', $showtime->id)
            ->where('expires_at', '>', now())
            ->get()
            ->keyBy('seat_id');

        // Get seat IDs that already have a confirmed booking — these are permanently taken
        $bookedSeatIds = BookingSeat::whereHas('booking', function ($q) use ($showtime) {
            $q->where('showtime_id', $showtime->id)->where('status', 'confirmed');
        })->pluck('seat_id')->toArray();

        // Optional auth — if user is logged in, we can show which locks belong to them
        $userId = auth('sanctum')->id();

        // Set status for each seat based on priority: booked > locked > available
        $seats->each(function ($seat) use ($activeLocks, $bookedSeatIds, $userId) {
            if (in_array($seat->id, $bookedSeatIds)) {
                // Permanently taken — payment was completed
                $seat->status = 'booked';
                $seat->is_mine = false;
            } elseif ($activeLocks->has($seat->id)) {
                // Someone is holding this seat right now
                $seat->status = 'locked';
                // is_mine tells the client if they are the one holding this lock
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
        summary: 'Lock seats for 5 minutes',
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
    // Locks the selected seats for 5 minutes so the user can complete their booking.
    // First-come-first-serve: if another user locks the same seat first, this returns 409.
    public function lock(LockSeatsRequest $request, Showtime $showtime): JsonResponse
    {
        $seatIds = $request->seat_ids;
        $userId = auth()->id();

        // Make sure all requested seats actually belong to this showtime's hall
        $validCount = Seat::where('hall_id', $showtime->hall_id)
            ->whereIn('id', $seatIds)
            ->count();

        if ($validCount !== count($seatIds)) {
            return response()->json(['message' => 'One or more seats do not belong to this showtime'], 422);
        }

        // Check if any of the seats are already locked by someone else
        $alreadyLocked = SeatLock::where('showtime_id', $showtime->id)
            ->whereIn('seat_id', $seatIds)
            ->where('expires_at', '>', now())
            ->exists();

        if ($alreadyLocked) {
            // 409 Conflict — seat is taken, not a validation error
            return response()->json(['message' => 'One or more seats are already locked'], 409);
        }

        $expiresAt = now()->addMinutes(5);

        try {
            // Wrap in a transaction so either all seats lock or none do
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
            // Two users clicked at the exact same time — database unique constraint caught it
            // Only one insert wins, the other gets this error
            return response()->json(['message' => 'One or more seats were just taken'], 409);
        }

        // Broadcast to all clients watching this showtime — seat map updates in real-time
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
            'message' => 'Seats locked for 5 minutes',
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
    // Releases all seat locks held by the current user for this showtime.
    // Called when user cancels or goes back from the booking screen.
    public function unlock(Showtime $showtime): JsonResponse
    {
        // Load locks with seat info before deleting so we can broadcast which seats were released
        $locks = SeatLock::where('showtime_id', $showtime->id)
            ->where('user_id', auth()->id())
            ->with('seat')
            ->get();

        SeatLock::where('showtime_id', $showtime->id)
            ->where('user_id', auth()->id())
            ->delete();

        // Tell all connected clients these seats are available again
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
