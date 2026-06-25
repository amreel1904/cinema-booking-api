<?php

namespace Database\Seeders;

use App\Models\FoodBeverage;
use Illuminate\Database\Seeder;

class FoodBeverageSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Regular Popcorn', 'description' => 'Classic salted popcorn', 'price' => 8.00, 'category' => 'food'],
            ['name' => 'Large Popcorn', 'description' => 'Large salted or caramel popcorn', 'price' => 12.00, 'category' => 'food'],
            ['name' => 'Hot Dog', 'description' => 'Classic beef hot dog with bun', 'price' => 10.00, 'category' => 'food'],
            ['name' => 'Nachos', 'description' => 'Tortilla chips with cheese dip', 'price' => 11.00, 'category' => 'food'],
            ['name' => 'Regular Coke', 'description' => '500ml Coca-Cola', 'price' => 5.00, 'category' => 'drink'],
            ['name' => 'Large Coke', 'description' => '700ml Coca-Cola', 'price' => 7.00, 'category' => 'drink'],
            ['name' => 'Mineral Water', 'description' => '500ml mineral water', 'price' => 3.00, 'category' => 'drink'],
            ['name' => 'Popcorn + Drink Combo', 'description' => 'Regular popcorn and regular coke', 'price' => 15.00, 'category' => 'combo'],
        ];

        foreach ($items as $item) {
            FoodBeverage::create($item);
        }
    }
}
