<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('testing_sessions', function (Blueprint $table) {
            $table->uuid('public_token')->nullable()->unique()->after('id');
            $table->unsignedInteger('current_step')->default(1)->after('status');
        });

        DB::table('testing_sessions')
            ->whereNull('public_token')
            ->orderBy('id')
            ->eachById(function ($session): void {
                DB::table('testing_sessions')
                    ->where('id', $session->id)
                    ->update(['public_token' => (string) Str::uuid()]);
            });
    }

    public function down(): void
    {
        Schema::table('testing_sessions', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropColumn(['public_token', 'current_step']);
        });
    }
};
