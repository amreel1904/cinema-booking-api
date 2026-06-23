<?php

namespace Database\Factories;

use App\Models\Hall;
use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShowtimeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'movie_id' => Movie::factory(),
            'hall_id' => Hall::factory(),
            'start_time' => $this->faker->dateTimeBetween('now', '+30 days'),
            'price' => $this->faker->randomElement([12.00, 14.00, 16.00, 18.00]),
        ];
    }
}
