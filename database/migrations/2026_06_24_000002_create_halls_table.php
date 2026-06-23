<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('halls', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('total_rows');
            $table->integer('seats_per_row');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('halls');
    }
};
