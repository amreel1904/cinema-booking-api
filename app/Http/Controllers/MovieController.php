<?php

namespace App\Http\Controllers;

use App\Http\Resources\MovieResource;
use App\Http\Resources\ShowtimeResource;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $movies = Movie::query()
            ->when($request->search, fn ($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->get();

        return response()->json([
            'data' => MovieResource::collection($movies),
            'message' => 'Movies retrieved',
        ]);
    }

    public function show(Movie $movie): JsonResponse
    {
        return response()->json([
            'data' => new MovieResource($movie),
            'message' => 'Movie retrieved',
        ]);
    }

    public function showtimes(Movie $movie): JsonResponse
    {
        $showtimes = $movie->showtimes()->with('hall')->get();

        return response()->json([
            'data' => ShowtimeResource::collection($showtimes),
            'message' => 'Showtimes retrieved',
        ]);
    }
}
