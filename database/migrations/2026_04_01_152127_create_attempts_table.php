<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('testing_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('session_id')->constrained('testing_sessions')->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('testing_tests')->cascadeOnDelete();

            $table->string('status', 30)->default('in_progress');

            $table->decimal('raw_score', 10, 2)->default(0);
            $table->decimal('weighted_score', 10, 2)->default(0);
            $table->decimal('max_score', 10, 2)->default(0);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();

            $table->unique(['session_id', 'test_id']);
            $table->index(['test_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testing_attempts');
    }
};
