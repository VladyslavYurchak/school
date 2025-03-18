<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lesson_test_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_test_id')->constrained()->onDelete('cascade'); // Прив’язка до питання
            $table->string('option_text'); // Текст варіанту відповіді
            $table->boolean('is_correct')->default(false); // Чи правильний варіант
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_test_options');
    }
};
