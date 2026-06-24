<?php

namespace Tests\Feature\Payment;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Hall;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function setupPendingBooking(): array
    {
        $user = User::factory()->create();
        $hall = Hall::factory()->create();
        $showtime = Showtime::factory()->create(['hall_id' => $hall->id, 'price' => 14.00]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id, 'row' => 'A', 'number' => 1]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'showtime_id' => $showtime->id,
            'status' => 'pending',
            'total_amount' => 14.00,
        ]);

        BookingSeat::create([
            'booking_id' => $booking->id,
            'seat_id' => $seat->id,
            'price' => 14.00,
        ]);

        $token = $user->createToken('auth')->plainTextToken;

        return [$booking, $user, $token];
    }

    public function test_can_get_payment_methods(): void
    {
        $response = $this->getJson('/api/payment-methods');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['method', 'label']]])
            ->assertJsonFragment(['method' => 'credit_card'])
            ->assertJsonFragment(['method' => 'online_banking']);
    }

    public function test_payment_methods_does_not_require_auth(): void
    {
        $response = $this->getJson('/api/payment-methods');

        $response->assertStatus(200);
    }

    public function test_user_can_pay_pending_booking(): void
    {
        [$booking, $user, $token] = $this->setupPendingBooking();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/bookings/{$booking->id}/payment", [
                'method' => 'credit_card',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['booking_id', 'status', 'method', 'amount'],
                'message',
            ])
            ->assertJsonFragment(['status' => 'confirmed', 'method' => 'credit_card']);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
        ]);

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'method' => 'credit_card',
            'status' => 'confirmed',
        ]);
    }

    public function test_cannot_pay_already_confirmed_booking(): void
    {
        [$booking, $user, $token] = $this->setupPendingBooking();
        $booking->update(['status' => 'confirmed']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/bookings/{$booking->id}/payment", [
                'method' => 'credit_card',
            ]);

        $response->assertStatus(422);
    }

    public function test_user_cannot_pay_another_users_booking(): void
    {
        [$booking] = $this->setupPendingBooking();

        $otherToken = User::factory()->create()->createToken('auth')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$otherToken}")
            ->postJson("/api/bookings/{$booking->id}/payment", [
                'method' => 'ewallet',
            ]);

        $response->assertStatus(404);
    }

    public function test_payment_requires_auth(): void
    {
        $response = $this->postJson('/api/bookings/1/payment', [
            'method' => 'credit_card',
        ]);

        $response->assertStatus(401);
    }
}
