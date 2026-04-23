<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('testing_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('testing_tests')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('testing_sections')->nullOnDelete();

            $table->string('type', 50)->default('single_choice');
            $table->string('title')->nullable();
            $table->text('question_text');
            $table->text('helper_text')->nullable();
            $table->text('content_before')->nullable();
            $table->text('content_after')->nullable();

            $table->decimal('default_correct_points', 8, 2)->default(1);
            $table->decimal('default_incorrect_points', 8, 2)->default(0);

            $table->string('difficulty_level', 20)->nullable();

            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['test_id', 'sort_order']);
            $table->index(['section_id', 'sort_order']);
            $table->index(['test_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testing_questions');
    }
};
