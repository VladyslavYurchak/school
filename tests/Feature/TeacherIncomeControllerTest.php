<?php

namespace Tests\Feature;

use App\Enums\LessonLogStatus;
use App\Enums\LessonType;
use App\Models\Group;
use App\Models\LessonLog;
use App\Models\PlannedLesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherIncomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_income_sums_group_and_pair_sessions_without_double_counting_old_logs(): void
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::factory()->create([
            'user_id' => $user->id,
            'group_lesson_price' => 900,
            'pair_lesson_price' => 700,
        ]);

        $group = Group::factory()->group()->create([
            'name' => 'Group A',
            'teacher_id' => $teacher->id,
        ]);

        $pair = Group::factory()->pair()->create([
            'name' => 'Pair A',
            'teacher_id' => $teacher->id,
        ]);

        $groupStudents = Student::factory()->count(3)->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
        ]);

        $pairStudents = Student::factory()->count(2)->create([
            'teacher_id' => $teacher->id,
            'group_id' => $pair->id,
        ]);

        $groupLesson = PlannedLesson::factory()->group()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
            'lesson_type' => LessonType::Group,
            'start_date' => '2026-06-10 10:00:00',
            'end_date' => '2026-06-10 11:00:00',
        ]);

        $pairLesson = PlannedLesson::factory()->pair()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $pair->id,
            'lesson_type' => LessonType::Pair,
            'start_date' => '2026-06-11 10:00:00',
            'end_date' => '2026-06-11 11:00:00',
        ]);

        foreach ($groupStudents as $student) {
            LessonLog::factory()->create([
                'lesson_id' => $groupLesson->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'group_id' => $group->id,
                'lesson_type' => LessonType::Group->value,
                'date' => '2026-06-10',
                'time' => '10:00:00',
                'status' => LessonLogStatus::Completed->value,
                'teacher_payout_amount' => null,
                'teacher_rate_amount_at_charge' => null,
            ]);
        }

        foreach ($pairStudents as $student) {
            LessonLog::factory()->create([
                'lesson_id' => $pairLesson->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'group_id' => $pair->id,
                'lesson_type' => LessonType::Pair->value,
                'date' => '2026-06-11',
                'time' => '10:00:00',
                'status' => LessonLogStatus::Completed->value,
                'teacher_payout_amount' => null,
                'teacher_rate_amount_at_charge' => null,
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->get(route('admin.teacher_income.index', [
                'month' => 6,
                'year' => 2026,
            ]));

        $response->assertOk();
        $response->assertSee('Group A');
        $response->assertSee('Pair A');
        $response->assertSee('900.00');
        $response->assertSee('700.00');
        $response->assertSee('1,600.00');
    }
}
