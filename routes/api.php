<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\FoodBeverageController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SeatController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::get('/movies', [MovieController::class, 'index']);
Route::get('/movies/{movie}', [MovieController::class, 'show']);
Route::get('/movies/{movie}/showtimes', [MovieController::class, 'showtimes']);

Route::get('/showtimes/{showtime}/seats', [SeatController::class, 'index']);

Route::get('/fnb', [FoodBeverageController::class, 'index']);

Route::get('/payment-methods', [PaymentController::class, 'methods']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/showtimes/{showtime}/seats/lock', [SeatController::class, 'lock']);
    Route::delete('/showtimes/{showtime}/seats/lock', [SeatController::class, 'unlock']);

    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);

    Route::post('/bookings/{booking}/payment', [PaymentController::class, 'store']);
});
