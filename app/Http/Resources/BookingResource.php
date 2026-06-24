<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'showtime' => $this->whenLoaded('showtime', fn () => [
                'id' => $this->showtime->id,
                'movie_title' => $this->showtime->movie->title ?? null,
                'start_time' => $this->showtime->start_time,
                'price' => $this->showtime->price,
            ]),
            'seats' => $this->whenLoaded('seats', fn () => $this->seats->map(fn ($bs) => [
                'seat_id' => $bs->seat_id,
                'row' => $bs->seat->row ?? null,
                'number' => $bs->seat->number ?? null,
                'price' => $bs->price,
            ])),
            'fnbs' => $this->whenLoaded('fnbs', fn () => $this->fnbs->map(fn ($bf) => [
                'name' => $bf->foodBeverage->name ?? null,
                'quantity' => $bf->quantity,
                'price' => $bf->price,
            ])),
            'payment' => $this->whenLoaded('payment', fn () => $this->payment ? [
                'method' => $this->payment->method,
                'status' => $this->payment->status,
                'amount' => $this->payment->amount,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}
