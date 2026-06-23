<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowtimeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'movie_id' => $this->movie_id,
            'hall_id' => $this->hall_id,
            'hall_name' => $this->whenLoaded('hall', fn () => $this->hall->name),
            'start_time' => $this->start_time,
            'price' => $this->price,
        ];
    }
}
