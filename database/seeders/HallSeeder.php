<?php

namespace Database\Seeders;

use App\Models\Hall;
use App\Models\Seat;
use Illuminate\Database\Seeder;

class HallSeeder extends Seeder
{
    public function run(): void
    {
        $halls = [
            ['name' => 'Hall 1', 'total_rows' => 5, 'seats_per_row' => 10],
            ['name' => 'Hall 2', 'total_rows' => 5, 'seats_per_row' => 10],
        ];

        foreach ($halls as $hallData) {
            $hall = Hall::create($hallData);

            // Create seats row A-E, number 1-10
            foreach (range('A', 'E') as $row) {
                foreach (range(1, 10) as $number) {
                    Seat::create([
                        'hall_id' => $hall->id,
                        'row' => $row,
                        'number' => $number,
                        'type' => ($row === 'A') ? 'premium' : 'standard',
                    ]);
                }
            }
        }
    }
}
