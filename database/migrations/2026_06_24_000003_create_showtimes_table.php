<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('showtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hall_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_time');
            $table->decimal('price', 8, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('showtimes');
    }
};
