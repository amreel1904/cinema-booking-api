<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class HallFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Hall ' . $this->faker->unique()->numberBetween(1, 20),
            'total_rows' => $this->faker->numberBetween(5, 10),
            'seats_per_row' => $this->faker->numberBetween(8, 15),
        ];
    }
}
