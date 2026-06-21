<?php

namespace Tests\Feature;

use App\Models\LessonAction;
use App\Models\PlannedLesson;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminHistoryActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_actions_use_admin_pagination_and_keep_teacher_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->create();
        $lesson = PlannedLesson::factory()->create([
            'teacher_id' => $teacher->id,
        ]);

        for ($i = 0; $i < 30; $i++) {
            LessonAction::create([
                'lesson_id' => $lesson->id,
                'user_id' => $admin->id,
                'action' => 'created',
                'lesson_datetime' => now()->addDays($i),
            ]);
        }

        $this
            ->actingAs($admin)
            ->get(route('admin.history_actions.index', ['teacher_id' => $teacher->id]))
            ->assertOk()
            ->assertSee('class="admin-page"', false)
            ->assertSee('class="admin-hero"', false)
            ->assertSee('admin-panel', false)
            ->assertDontSee('<style>', false)
            ->assertDontSee('badge-history', false)
            ->assertSee('<ul class="pagination">', false)
            ->assertSee('teacher_id='.$teacher->id.'&amp;page=2', false);
    }
}
