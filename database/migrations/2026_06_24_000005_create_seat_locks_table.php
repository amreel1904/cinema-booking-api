<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seat_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('showtime_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('expires_at');
            $table->timestamps();
            $table->unique(['showtime_id', 'seat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_locks');
    }
};
