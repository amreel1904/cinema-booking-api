<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class SubmitPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'method' => ['required', 'string', 'in:credit_card,debit_card,online_banking,ewallet'],
        ];
    }
}
