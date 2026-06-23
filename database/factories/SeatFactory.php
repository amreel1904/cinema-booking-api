<?php

namespace Database\Factories;

use App\Models\Hall;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'hall_id' => Hall::factory(),
            'row' => $this->faker->randomElement(['A', 'B', 'C', 'D', 'E']),
            'number' => $this->faker->numberBetween(1, 15),
            'type' => $this->faker->randomElement(['standard', 'premium']),
        ];
    }
}
