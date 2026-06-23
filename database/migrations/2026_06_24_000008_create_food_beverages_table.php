<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('food_beverages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2);
            $table->enum('category', ['food', 'drink', 'combo']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_beverages');
    }
};
