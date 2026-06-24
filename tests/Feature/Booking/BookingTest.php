<?php

namespace Tests\Feature\Booking;

use App\Models\FoodBeverage;
use App\Models\Hall;
use App\Models\Movie;
use App\Models\Seat;
use App\Models\SeatLock;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private function setupBookingScenario(): array
    {
        $hall = Hall::factory()->create();
        $movie = Movie::factory()->create();
        $showtime = Showtime::factory()->create([
            'movie_id' => $movie->id,
            'hall_id' => $hall->id,
            'price' => 14.00,
        ]);
        $seat = Seat::factory()->create([
            'hall_id' => $hall->id,
            'row' => 'A',
            'number' => 1,
        ]);
        $user = User::factory()->create();

        SeatLock::create([
            'showtime_id' => $showtime->id,
            'seat_id' => $seat->id,
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes(10),
        ]);

        $token = $user->createToken('auth')->plainTextToken;

        return [$showtime, $seat, $user, $token];
    }

    public function test_user_can_create_booking(): void
    {
        [$showtime, $seat, $user, $token] = $this->setupBookingScenario();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/bookings', [
                'showtime_id' => $showtime->id,
                'seat_ids' => [$seat->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'status', 'total_amount', 'showtime', 'seats'],
                'message',
            ])
            ->assertJsonFragment(['status' => 'pending', 'total_amount' => '14.00']);
    }

    public function test_booking_removes_seat_locks(): void
    {
        [$showtime, $seat, $user, $token] = $this->setupBookingScenario();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/bookings', [
                'showtime_id' => $showtime->id,
                'seat_ids' => [$seat->id],
            ]);

        $this->assertDatabaseMissing('seat_locks', [
            'showtime_id' => $showtime->id,
            'seat_id' => $seat->id,
        ]);
    }

    public function test_booking_fails_without_seat_lock(): void
    {
        $hall = Hall::factory()->create();
        $showtime = Showtime::factory()->create(['hall_id' => $hall->id]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id, 'row' => 'A', 'number' => 1]);
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/bookings', [
                'showtime_id' => $showtime->id,
                'seat_ids' => [$seat->id],
            ]);

        $response->assertStatus(422);
    }

    public function test_booking_with_fnb(): void
    {
        [$showtime, $seat, $user, $token] = $this->setupBookingScenario();
        $fnb = FoodBeverage::factory()->create(['price' => 8.00]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/bookings', [
                'showtime_id' => $showtime->id,
                'seat_ids' => [$seat->id],
                'fnb' => [['id' => $fnb->id, 'quantity' => 2]],
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['total_amount' => '30.00']);
    }

    public function test_user_can_get_their_booking(): void
    {
        [$showtime, $seat, $user, $token] = $this->setupBookingScenario();

        $create = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/bookings', [
                'showtime_id' => $showtime->id,
                'seat_ids' => [$seat->id],
            ]);

        $bookingId = $create->json('data.id');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/bookings/{$bookingId}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'status', 'total_amount', 'seats'],
            ]);
    }

    public function test_user_cannot_view_another_users_booking(): void
    {
        $hall = Hall::factory()->create();
        $showtime = Showtime::factory()->create(['hall_id' => $hall->id]);
        $owner = User::factory()->create();
        $other = User::factory()->create();

        // Create booking directly so no HTTP auth state is cached before our test request
        $booking = \App\Models\Booking::create([
            'user_id' => $owner->id,
            'showtime_id' => $showtime->id,
            'status' => 'pending',
            'total_amount' => 14.00,
        ]);

        $otherToken = $other->createToken('auth')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$otherToken}")
            ->getJson("/api/bookings/{$booking->id}");

        $response->assertStatus(404);
    }

    public function test_booking_requires_auth(): void
    {
        $response = $this->postJson('/api/bookings', [
            'showtime_id' => 1,
            'seat_ids' => [1],
        ]);

        $response->assertStatus(401);
    }
}
