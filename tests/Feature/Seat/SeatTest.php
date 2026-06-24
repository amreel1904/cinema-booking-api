<?php

namespace Tests\Feature\Seat;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Hall;
use App\Models\Movie;
use App\Models\Seat;
use App\Models\SeatLock;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeatTest extends TestCase
{
    use RefreshDatabase;

    private function createShowtimeWithSeats(): array
    {
        $hall = Hall::factory()->create();
        $movie = Movie::factory()->create();
        $showtime = Showtime::factory()->create([
            'movie_id' => $movie->id,
            'hall_id' => $hall->id,
        ]);

        $seats = [];
        foreach (['A', 'B'] as $row) {
            foreach ([1, 2, 3] as $number) {
                $seats[] = Seat::factory()->create([
                    'hall_id' => $hall->id,
                    'row' => $row,
                    'number' => $number,
                ]);
            }
        }

        return [$showtime, $seats];
    }

    public function test_can_get_seat_map(): void
    {
        [$showtime, $seats] = $this->createShowtimeWithSeats();

        $response = $this->getJson("/api/showtimes/{$showtime->id}/seats");

        $response->assertStatus(200)
            ->assertJsonCount(6, 'data')
            ->assertJsonFragment(['status' => 'available']);
    }

    public function test_locked_seat_shows_as_locked(): void
    {
        [$showtime, $seats] = $this->createShowtimeWithSeats();
        $user = User::factory()->create();

        SeatLock::create([
            'showtime_id' => $showtime->id,
            'seat_id' => $seats[0]->id,
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->getJson("/api/showtimes/{$showtime->id}/seats");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $seats[0]->id, 'status' => 'locked']);
    }

    public function test_expired_lock_shows_seat_as_available(): void
    {
        [$showtime, $seats] = $this->createShowtimeWithSeats();
        $user = User::factory()->create();

        SeatLock::create([
            'showtime_id' => $showtime->id,
            'seat_id' => $seats[0]->id,
            'user_id' => $user->id,
            'expires_at' => now()->subMinutes(1),
        ]);

        $response = $this->getJson("/api/showtimes/{$showtime->id}/seats");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $seats[0]->id, 'status' => 'available']);
    }

    public function test_booked_seat_shows_as_booked(): void
    {
        [$showtime, $seats] = $this->createShowtimeWithSeats();
        $user = User::factory()->create();

        $booking = Booking::create([
            'user_id' => $user->id,
            'showtime_id' => $showtime->id,
            'status' => 'confirmed',
            'total_amount' => 14.00,
        ]);

        BookingSeat::create([
            'booking_id' => $booking->id,
            'seat_id' => $seats[0]->id,
            'price' => 14.00,
        ]);

        $response = $this->getJson("/api/showtimes/{$showtime->id}/seats");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $seats[0]->id, 'status' => 'booked']);
    }

    public function test_is_mine_is_true_for_authenticated_user_own_lock(): void
    {
        [$showtime, $seats] = $this->createShowtimeWithSeats();
        $user = User::factory()->create();

        SeatLock::create([
            'showtime_id' => $showtime->id,
            'seat_id' => $seats[0]->id,
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes(10),
        ]);

        $token = $user->createToken('auth')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/showtimes/{$showtime->id}/seats");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $seats[0]->id, 'status' => 'locked', 'is_mine' => true]);
    }

    public function test_user_can_lock_available_seats(): void
    {
        [$showtime, $seats] = $this->createShowtimeWithSeats();
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/showtimes/{$showtime->id}/seats/lock", [
                'seat_ids' => [$seats[0]->id, $seats[1]->id],
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['seat_ids', 'expires_at'], 'message']);

        $this->assertDatabaseHas('seat_locks', [
            'showtime_id' => $showtime->id,
            'seat_id' => $seats[0]->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_cannot_lock_already_locked_seat(): void
    {
        [$showtime, $seats] = $this->createShowtimeWithSeats();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        SeatLock::create([
            'showtime_id' => $showtime->id,
            'seat_id' => $seats[0]->id,
            'user_id' => $user1->id,
            'expires_at' => now()->addMinutes(10),
        ]);

        $token = $user2->createToken('auth')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/showtimes/{$showtime->id}/seats/lock", [
                'seat_ids' => [$seats[0]->id],
            ]);

        $response->assertStatus(409);
    }

    public function test_lock_requires_auth(): void
    {
        [$showtime, $seats] = $this->createShowtimeWithSeats();

        $response = $this->postJson("/api/showtimes/{$showtime->id}/seats/lock", [
            'seat_ids' => [$seats[0]->id],
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_release_their_own_lock(): void
    {
        [$showtime, $seats] = $this->createShowtimeWithSeats();
        $user = User::factory()->create();

        SeatLock::create([
            'showtime_id' => $showtime->id,
            'seat_id' => $seats[0]->id,
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes(10),
        ]);

        $token = $user->createToken('auth')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/showtimes/{$showtime->id}/seats/lock");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('seat_locks', [
            'showtime_id' => $showtime->id,
            'user_id' => $user->id,
        ]);
    }
}
