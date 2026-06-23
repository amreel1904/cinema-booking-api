<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MovieFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->unique()->sentence(3),
            'description' => $this->faker->paragraph(),
            'genre' => $this->faker->randomElement(['Action', 'Comedy', 'Drama', 'Horror', 'Sci-Fi']),
            'duration' => $this->faker->numberBetween(80, 180),
            'rating' => $this->faker->randomElement(['U', 'P13', '18']),
            'poster_url' => null,
        ];
    }
}
