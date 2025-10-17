<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_logs', function (Blueprint $table) {
            $table->id();

            // Основні зв'язки
            $table->foreignId('lesson_id')
                ->nullable()
                ->constrained('planned_lessons')
                ->nullOnDelete();

            $table->foreignId('student_id')
                ->nullable()
                ->constrained('students')
                ->nullOnDelete();

            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->nullOnDelete();

            $table->foreignId('group_id')
                ->nullable()
                ->constrained('groups')
                ->nullOnDelete();

            // Тип і базова інформація
            $table->string('lesson_type')->nullable(); // individual, group, pair, trial
            $table->date('date');
            $table->time('time');
            $table->integer('duration')->nullable(); // хвилини
            $table->string('status')->default('completed');
            $table->text('notes')->nullable();

            // Snapshot оплати викладача (фіксується при фіналізації уроку)
            $table->decimal('teacher_rate_amount_at_charge', 10, 2)->nullable(); // ставка на момент уроку
            $table->enum('teacher_payout_basis', ['per_lesson', 'per_hour', 'per_student', 'custom'])->nullable();
            $table->decimal('teacher_payout_amount', 10, 2)->nullable(); // скільки викладачу нараховано
            $table->timestamp('charged_at')->nullable(); // коли фіналізовано/нараховано

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_logs');
    }
};
