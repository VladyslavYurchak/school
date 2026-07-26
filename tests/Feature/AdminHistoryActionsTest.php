<?php

namespace Tests\Feature;

use App\Models\LessonAction;
use App\Models\Group;
use App\Models\PlannedLesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\LessonActionLogger;
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

    public function test_history_identifies_pair_and_teacher_after_lesson_is_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->create([
            'first_name' => 'History',
            'last_name' => 'Teacher',
        ]);
        $group = Group::factory()->create([
            'name' => 'History Pair',
            'type' => 'pair',
            'teacher_id' => $teacher->id,
        ]);
        $student = Student::factory()->create([
            'first_name' => 'First',
            'last_name' => 'Student',
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
        ]);
        $lesson = PlannedLesson::factory()->create([
            'title' => 'Pair speaking practice',
            'lesson_type' => 'pair',
            'teacher_id' => $teacher->id,
            'student_id' => null,
            'group_id' => $group->id,
        ]);

        LessonActionLogger::log(
            lessonId: $lesson->id,
            action: 'cancelled',
            lessonDatetime: $lesson->start_date->toDateTimeString(),
            userId: $admin->id,
        );

        $lesson->forceDelete();

        $this->actingAs($admin)
            ->get(route('admin.history_actions.index', ['teacher_id' => $teacher->id]))
            ->assertOk()
            ->assertSee('Парне')
            ->assertSee('History Pair')
            ->assertSee('Pair speaking practice')
            ->assertSee('History Teacher')
            ->assertDontSee('Урок видалено');
    }
}
