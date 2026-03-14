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
        Schema::create('lesson_actions', function (Blueprint $table) {
            $table->id();

            // Запланований урок
            $table->unsignedBigInteger('lesson_id')->index();

            // Хто зробив дію (admin / teacher / system)
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // created | rescheduled | cancelled | completed
            $table->string('action', 50);

            // Дата/час уроку на момент дії
            $table->dateTime('lesson_datetime')->nullable();

            // Нова дата/час (тільки для перенесення)
            $table->dateTime('new_lesson_datetime')->nullable();

            // Будь-яка додаткова інфа
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_actions');
    }
};
