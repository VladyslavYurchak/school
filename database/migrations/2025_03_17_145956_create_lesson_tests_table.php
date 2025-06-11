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
        Schema::create('lesson_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->onDelete('cascade'); // Прив’язка до уроку
            $table->unsignedInteger('position')->default(1);
            $table->text('question'); // Текст запитання
            $table->boolean('is_multiple_choice')->default(false); // Чи є багатовибірний тест
            $table->string('correct_answer')->nullable(); // Правильна відповідь (якщо текст)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_tests');
    }
};
