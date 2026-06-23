<?php

namespace Tests\Feature\Movie;

use App\Models\Hall;
use App\Models\Movie;
use App\Models\Showtime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovieTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_movies(): void
    {
        Movie::factory()->count(3)->create();

        $response = $this->getJson('/api/movies');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_movie_list_is_empty_when_no_movies(): void
    {
        $response = $this->getJson('/api/movies');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_can_search_movies_by_title(): void
    {
        Movie::factory()->create(['title' => 'Avengers Endgame']);
        Movie::factory()->create(['title' => 'Batman Begins']);

        $response = $this->getJson('/api/movies?search=Avengers');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_get_movie_detail(): void
    {
        $movie = Movie::factory()->create();

        $response = $this->getJson("/api/movies/{$movie->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'title', 'description', 'genre', 'duration', 'rating', 'poster_url'],
            ]);
    }

    public function test_movie_detail_returns_404_when_not_found(): void
    {
        $response = $this->getJson('/api/movies/999');

        $response->assertStatus(404);
    }

    public function test_can_list_showtimes_for_movie(): void
    {
        $movie = Movie::factory()->create();
        $hall = Hall::factory()->create();
        Showtime::factory()->count(2)->create([
            'movie_id' => $movie->id,
            'hall_id' => $hall->id,
        ]);

        $response = $this->getJson("/api/movies/{$movie->id}/showtimes");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_showtimes_list_is_empty_when_no_showtimes(): void
    {
        $movie = Movie::factory()->create();

        $response = $this->getJson("/api/movies/{$movie->id}/showtimes");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }
}
