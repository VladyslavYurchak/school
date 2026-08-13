<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('telegram_payment_confirmations')) {
            Schema::create('telegram_payment_confirmations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->unique()->constrained()->cascadeOnDelete();
                $table->foreignId('telegram_account_id')->constrained()->cascadeOnDelete();
                $table->string('status')->default('pending');
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamp('last_attempt_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'last_attempt_at']);
            });
        }

        if (! Schema::hasTable('telegram_lesson_absence_requests')) {
            Schema::create('telegram_lesson_absence_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('planned_lesson_id')->constrained()->cascadeOnDelete();
                $table->foreignId('student_id')->constrained()->cascadeOnDelete();
                $table->foreignId('telegram_account_id')->constrained()->cascadeOnDelete();
                $table->string('status')->default('requested');
                $table->timestamp('requested_at');
                $table->timestamps();

                $table->unique(
                    ['planned_lesson_id', 'student_id'],
                    'telegram_lesson_absence_student_unique',
                );
            });
        }

        if (! Schema::hasTable('telegram_homework_assignments')) {
            Schema::create('telegram_homework_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('planned_lesson_id')->unique()->constrained()->cascadeOnDelete();
                $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
                $table->text('text')->nullable();
                $table->string('telegram_file_id')->nullable();
                $table->string('telegram_file_type')->nullable();
                $table->string('file_name')->nullable();
                $table->timestamp('assigned_at');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('telegram_homework_submissions')) {
            Schema::create('telegram_homework_submissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('telegram_homework_assignment_id');
                $table->foreign(
                    'telegram_homework_assignment_id',
                    'tg_hw_submission_assignment_fk',
                )->references('id')->on('telegram_homework_assignments')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained()->cascadeOnDelete();
                $table->text('text')->nullable();
                $table->string('telegram_file_id')->nullable();
                $table->string('telegram_file_type')->nullable();
                $table->string('file_name')->nullable();
                $table->string('status')->default('submitted');
                $table->timestamp('submitted_at');
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->unique(
                    ['telegram_homework_assignment_id', 'student_id'],
                    'telegram_homework_submission_student_unique',
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_homework_submissions');
        Schema::dropIfExists('telegram_homework_assignments');
        Schema::dropIfExists('telegram_lesson_absence_requests');
        Schema::dropIfExists('telegram_payment_confirmations');
    }
};
