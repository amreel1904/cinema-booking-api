<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'showtime_id' => ['required', 'integer', 'exists:showtimes,id'],
            'seat_ids' => ['required', 'array', 'min:1'],
            'seat_ids.*' => ['required', 'integer', 'exists:seats,id'],
            'fnb' => ['nullable', 'array'],
            'fnb.*.id' => ['required_with:fnb', 'integer', 'exists:food_beverages,id'],
            'fnb.*.quantity' => ['required_with:fnb', 'integer', 'min:1'],
        ];
    }
}
