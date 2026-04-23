<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('testing_sessions', function (Blueprint $table) {
            $table->id();

            $table->string('language_code', 10);
            $table->string('status', 30)->default('in_progress');

            $table->decimal('total_raw_score', 10, 2)->default(0);
            $table->decimal('total_weighted_score', 10, 2)->default(0);
            $table->decimal('max_weighted_score', 10, 2)->default(0);

            $table->string('detected_level', 20)->nullable();
            $table->foreignId('result_range_id')->nullable()->constrained('testing_result_ranges')->nullOnDelete();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index(['language_code', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testing_sessions');
    }
};
