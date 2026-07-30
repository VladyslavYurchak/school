<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_account_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('planned_lesson_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('type');
            $table->string('status')->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['telegram_account_id', 'planned_lesson_id', 'type'],
                'telegram_notification_lesson_unique',
            );
            $table->index(['status', 'last_attempt_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_notifications');
    }
};
