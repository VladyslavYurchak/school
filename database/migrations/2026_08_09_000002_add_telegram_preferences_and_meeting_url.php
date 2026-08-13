<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_accounts', function (Blueprint $table) {
            $table->boolean('lesson_reminders_enabled')->default(true)->after('notifications_enabled');
            $table->boolean('payment_notifications_enabled')->default(true)->after('lesson_reminders_enabled');
            $table->boolean('homework_notifications_enabled')->default(true)->after('payment_notifications_enabled');
            $table->unsignedSmallInteger('lesson_reminder_minutes')->default(60)->after('homework_notifications_enabled');
        });

        Schema::table('planned_lessons', function (Blueprint $table) {
            $table->string('meeting_url', 2048)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('planned_lessons', function (Blueprint $table) {
            $table->dropColumn('meeting_url');
        });

        Schema::table('telegram_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'lesson_reminders_enabled',
                'payment_notifications_enabled',
                'homework_notifications_enabled',
                'lesson_reminder_minutes',
            ]);
        });
    }
};
