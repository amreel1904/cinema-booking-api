<?php

namespace App\Http\Controllers;

use App\Http\Resources\MovieResource;
use App\Http\Resources\ShowtimeResource;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MovieController extends Controller
{
    #[OA\Get(
        path: '/api/movies',
        summary: 'List all movies',
        tags: ['Movies'],
        parameters: [new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Search by title')],
        responses: [new OA\Response(response: 200, description: 'Movies retrieved')]
    )]
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

    #[OA\Get(
        path: '/api/movies/{id}',
        summary: 'Get movie detail',
        tags: ['Movies'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Movie retrieved'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Movie $movie): JsonResponse
    {
        return response()->json([
            'data' => new MovieResource($movie),
            'message' => 'Movie retrieved',
        ]);
    }

    #[OA\Get(
        path: '/api/movies/{id}/showtimes',
        summary: 'List showtimes for a movie',
        tags: ['Movies'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Showtimes retrieved')]
    )]
    public function showtimes(Movie $movie): JsonResponse
    {
        $showtimes = $movie->showtimes()->with('hall')->get();

        return response()->json([
            'data' => ShowtimeResource::collection($showtimes),
            'message' => 'Showtimes retrieved',
        ]);
    }
}
