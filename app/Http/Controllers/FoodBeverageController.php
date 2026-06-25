<?php

namespace App\Http\Controllers;

use App\Http\Resources\FoodBeverageResource;
use App\Models\FoodBeverage;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class FoodBeverageController extends Controller
{
    #[OA\Get(
        path: '/api/fnb',
        summary: 'List all food and beverage items',
        tags: ['Food & Beverage'],
        responses: [new OA\Response(response: 200, description: 'Food and beverages retrieved')]
    )]
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => FoodBeverageResource::collection(FoodBeverage::all()),
            'message' => 'Food and beverages retrieved',
        ]);
    }
}
