<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('testing_tests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('language_code', 10);
            $table->text('description')->nullable();
            $table->text('intro_text')->nullable();

            $table->decimal('weight', 8, 2)->default(1);
            $table->decimal('max_score', 8, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false);
            $table->boolean('randomize_questions')->default(false);
            $table->boolean('show_result_immediately')->default(true);

            $table->unsignedInteger('time_limit_minutes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['language_code', 'is_active']);
            $table->index(['is_public', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testing_tests');
    }
};
