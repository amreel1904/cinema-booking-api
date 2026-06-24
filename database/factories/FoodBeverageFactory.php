<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FoodBeverageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomElement([5.00, 7.50, 8.00, 10.00, 12.00]),
            'category' => $this->faker->randomElement(['food', 'drink', 'combo']),
        ];
    }
}
