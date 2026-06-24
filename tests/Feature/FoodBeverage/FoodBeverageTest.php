<?php

namespace Tests\Feature\FoodBeverage;

use App\Models\FoodBeverage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodBeverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_fnb_items(): void
    {
        FoodBeverage::factory()->count(4)->create();

        $response = $this->getJson('/api/fnb');

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'name', 'description', 'price', 'category']],
            ]);
    }

    public function test_fnb_list_is_empty_when_no_items(): void
    {
        $response = $this->getJson('/api/fnb');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_fnb_does_not_require_auth(): void
    {
        $response = $this->getJson('/api/fnb');

        $response->assertStatus(200);
    }
}
