<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lesson_exercise_items', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('answer');
            $table->string('audio_path')->nullable()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_exercise_items', function (Blueprint $table) {
            $table->dropColumn(['settings', 'audio_path']);
        });
    }
};
