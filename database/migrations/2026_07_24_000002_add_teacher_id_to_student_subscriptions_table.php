<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_subscriptions', function (Blueprint $table) {
            $table->foreignId('teacher_id')
                ->nullable()
                ->after('student_id')
                ->constrained('teachers')
                ->nullOnDelete();
        });

        DB::table('student_subscriptions')
            ->select(['id', 'student_id'])
            ->orderBy('id')
            ->chunkById(200, function ($subscriptions) {
                $teacherIds = DB::table('students')
                    ->whereIn('id', $subscriptions->pluck('student_id'))
                    ->pluck('teacher_id', 'id');

                foreach ($subscriptions as $subscription) {
                    DB::table('student_subscriptions')
                        ->where('id', $subscription->id)
                        ->update([
                            'teacher_id' => $teacherIds[$subscription->student_id] ?? null,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('student_subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('teacher_id');
        });
    }
};
