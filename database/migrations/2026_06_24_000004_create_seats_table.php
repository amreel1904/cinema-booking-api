<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hall_id')->constrained()->cascadeOnDelete();
            $table->string('row', 1);
            $table->integer('number');
            $table->enum('type', ['standard', 'premium'])->default('standard');
            $table->timestamps();
            $table->unique(['hall_id', 'row', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
