<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lesson_exercise_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_exercise_id')->constrained()->cascadeOnDelete();
            $table->string('prompt');
            $table->string('answer');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['lesson_exercise_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_exercise_items');
    }
};
