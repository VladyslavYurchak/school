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
            $table->string('subscription_title')->nullable()->after('subscription_template_id');
            $table->string('lesson_type')->nullable()->after('subscription_title');
            $table->unsignedInteger('subscription_lessons_per_week')->nullable()->after('lesson_type');
        });

        DB::table('student_subscriptions')
            ->select(['id', 'subscription_template_id', 'type'])
            ->orderBy('id')
            ->chunkById(200, function ($subscriptions) {
                $templates = DB::table('subscription_templates')
                    ->whereIn('id', $subscriptions->pluck('subscription_template_id')->filter())
                    ->get(['id', 'title', 'type', 'lessons_per_week'])
                    ->keyBy('id');

                foreach ($subscriptions as $subscription) {
                    $template = $templates->get($subscription->subscription_template_id);

                    DB::table('student_subscriptions')
                        ->where('id', $subscription->id)
                        ->update([
                            'subscription_title' => $template?->title,
                            'lesson_type' => $template?->type
                                ?? ($subscription->type === 'single' ? 'individual' : null),
                            'subscription_lessons_per_week' => $template?->lessons_per_week,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('student_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_title',
                'lesson_type',
                'subscription_lessons_per_week',
            ]);
        });
    }
};
