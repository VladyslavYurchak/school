<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planned_lessons', function (Blueprint $table) {
            $table->index(
                ['status', 'start_date'],
                'planned_lessons_reminder_lookup_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('planned_lessons', function (Blueprint $table) {
            $table->dropIndex('planned_lessons_reminder_lookup_index');
        });
    }
};
