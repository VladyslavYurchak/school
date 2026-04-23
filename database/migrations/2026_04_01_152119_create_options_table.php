<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('testing_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('testing_questions')->cascadeOnDelete();

            $table->text('option_text');
            $table->string('option_value')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->decimal('points', 8, 2)->nullable();
            $table->text('explanation')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['question_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testing_options');
    }
};
