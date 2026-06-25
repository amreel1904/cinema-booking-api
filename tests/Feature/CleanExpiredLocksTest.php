<?php

namespace Tests\Feature;

use App\Models\Hall;
use App\Models\Seat;
use App\Models\SeatLock;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanExpiredLocksTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_locks_are_removed(): void
    {
        $user = User::factory()->create();
        $hall = Hall::factory()->create();
        $showtime = Showtime::factory()->create(['hall_id' => $hall->id]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id, 'row' => 'A', 'number' => 1]);

        SeatLock::create([
            'showtime_id' => $showtime->id,
            'seat_id' => $seat->id,
            'user_id' => $user->id,
            'expires_at' => now()->subMinutes(1), // expired 1 minute ago
        ]);

        $this->artisan('seats:clean-expired')->assertSuccessful();

        $this->assertDatabaseMissing('seat_locks', [
            'showtime_id' => $showtime->id,
            'seat_id' => $seat->id,
        ]);
    }

    public function test_active_locks_are_not_removed(): void
    {
        $user = User::factory()->create();
        $hall = Hall::factory()->create();
        $showtime = Showtime::factory()->create(['hall_id' => $hall->id]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id, 'row' => 'A', 'number' => 1]);

        SeatLock::create([
            'showtime_id' => $showtime->id,
            'seat_id' => $seat->id,
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes(9), // still active
        ]);

        $this->artisan('seats:clean-expired')->assertSuccessful();

        $this->assertDatabaseHas('seat_locks', [
            'showtime_id' => $showtime->id,
            'seat_id' => $seat->id,
        ]);
    }
}
