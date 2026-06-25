<?php

namespace Database\Seeders;

use App\Models\Movie;
use Illuminate\Database\Seeder;

class MovieSeeder extends Seeder
{
    public function run(): void
    {
        $movies = [
            ['title' => 'Avengers: Endgame', 'description' => 'The Avengers assemble one last time to reverse Thanos\'s actions.', 'genre' => 'Action', 'duration' => 181, 'rating' => 'P13', 'poster_url' => null],
            ['title' => 'The Dark Knight', 'description' => 'Batman faces his greatest enemy, the Joker.', 'genre' => 'Action', 'duration' => 152, 'rating' => 'P13', 'poster_url' => null],
            ['title' => 'Interstellar', 'description' => 'A team of explorers travel through a wormhole in space.', 'genre' => 'Sci-Fi', 'duration' => 169, 'rating' => 'U', 'poster_url' => null],
            ['title' => 'Parasite', 'description' => 'A poor family schemes to become employed by a wealthy household.', 'genre' => 'Drama', 'duration' => 132, 'rating' => '18', 'poster_url' => null],
            ['title' => 'Dune: Part Two', 'description' => 'Paul Atreides unites with the Fremen to seek revenge.', 'genre' => 'Sci-Fi', 'duration' => 166, 'rating' => 'P13', 'poster_url' => null],
        ];

        foreach ($movies as $movie) {
            Movie::create($movie);
        }
    }
}
