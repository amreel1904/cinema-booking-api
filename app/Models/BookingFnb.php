<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingFnb extends Model
{
    protected $fillable = ['booking_id', 'food_beverage_id', 'quantity', 'price'];

    protected $casts = ['price' => 'decimal:2'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function foodBeverage(): BelongsTo
    {
        return $this->belongsTo(FoodBeverage::class);
    }
}
