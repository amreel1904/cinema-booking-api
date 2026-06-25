<?php

namespace Database\Seeders;

use App\Models\Hall;
use App\Models\Movie;
use App\Models\Showtime;
use Illuminate\Database\Seeder;

class ShowtimeSeeder extends Seeder
{
    public function run(): void
    {
        $movies = Movie::all();
        $halls = Hall::all();

        foreach ($movies as $i => $movie) {
            Showtime::create([
                'movie_id' => $movie->id,
                'hall_id' => $halls[$i % $halls->count()]->id,
                'start_time' => now()->addDays($i + 1)->setTime(20, 0),
                'price' => 14.00,
            ]);

            Showtime::create([
                'movie_id' => $movie->id,
                'hall_id' => $halls[($i + 1) % $halls->count()]->id,
                'start_time' => now()->addDays($i + 1)->setTime(22, 30),
                'price' => 16.00,
            ]);
        }
    }
}
