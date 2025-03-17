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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('description');
            $table->text('content')->nullable();
            $table->string('lesson_type')->default('Reading');
            $table->unsignedInteger('position')->default(0);

            //дані уроку, домашка
            $table->text('media_files')->nullable(); // JSON для збереження кількох файлів
            $table->string('audio_file')->nullable(); // Додаємо колонку для аудіофайлу
            $table->string('video_url')->nullable(); // Посилання на YouTube
            $table->text('homework_text')->nullable(); // Текст домашнього завдання
            $table->text('homework_files')->nullable(); // JSON для файлів домашнього
            $table->string('homework_video_url')->nullable(); // Посилання на відео для домашнього
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
