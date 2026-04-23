<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('testing_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attempt_id')->constrained('testing_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('testing_questions')->cascadeOnDelete();
            $table->foreignId('selected_option_id')->nullable()->constrained('testing_options')->nullOnDelete();

            $table->text('answer_text')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('awarded_points', 8, 2)->nullable();

            $table->timestamps();

            // нормальні індекси
            $table->index('attempt_id');
            $table->index('question_id');
            $table->index('selected_option_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testing_answers');
    }
};
