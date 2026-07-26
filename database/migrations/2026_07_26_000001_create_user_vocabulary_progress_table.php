<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_vocabulary_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vocabulary_item_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('learning');
            $table->unsignedInteger('correct_answers')->default(0);
            $table->unsignedInteger('incorrect_answers')->default(0);
            $table->unsignedInteger('correct_streak')->default(0);
            $table->timestamp('learned_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamp('next_review_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'vocabulary_item_id']);
            $table->index(['user_id', 'status', 'next_review_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_vocabulary_progress');
    }
};
