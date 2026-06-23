<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'genre', 'duration', 'rating', 'poster_url'];

    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }
}
