<?php

namespace App\Http\Requests\Seat;

use Illuminate\Foundation\Http\FormRequest;

class LockSeatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seat_ids' => ['required', 'array', 'min:1'],
            'seat_ids.*' => ['required', 'integer', 'exists:seats,id'],
        ];
    }
}
