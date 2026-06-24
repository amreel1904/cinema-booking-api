<?php

namespace App\Http\Controllers;

use App\Http\Resources\FoodBeverageResource;
use App\Models\FoodBeverage;
use Illuminate\Http\JsonResponse;

class FoodBeverageController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => FoodBeverageResource::collection(FoodBeverage::all()),
            'message' => 'Food and beverages retrieved',
        ]);
    }
}
