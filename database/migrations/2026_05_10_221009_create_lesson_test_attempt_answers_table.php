<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_test_attempt_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lesson_test_attempt_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('lesson_test_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->json('selected_option_ids')->nullable();
            $table->text('answer_text')->nullable();

            $table->boolean('is_correct')->default(false);

            $table->timestamps();

            $table->index(['lesson_test_attempt_id', 'lesson_test_id'], 'attempt_test_idx');        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_test_attempt_answers');
    }
};
