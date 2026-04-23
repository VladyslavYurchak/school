<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('testing_result_ranges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->nullable()->constrained('testing_tests')->cascadeOnDelete();

            $table->string('title');
            $table->string('level_code', 20)->nullable();
            $table->decimal('min_score', 8, 2);
            $table->decimal('max_score', 8, 2);
            $table->text('description')->nullable();
            $table->text('recommendation_text')->nullable();

            $table->timestamps();

            $table->index(['test_id', 'min_score', 'max_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testing_result_ranges');
    }
};
