<?php

namespace Tests\Feature;

use App\Models\LessonLog;
use App\Models\PlannedLesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTeacherLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_archives_teacher_without_deleting_lesson_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->create();
        $lesson = PlannedLesson::factory()->create([
            'teacher_id' => $teacher->id,
        ]);
        $log = LessonLog::factory()->create([
            'lesson_id' => $lesson->id,
            'teacher_id' => $teacher->id,
        ]);

        $this
            ->actingAs($admin)
            ->delete(route('admin.teachers.destroy', $teacher))
            ->assertRedirect(route('admin.teachers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('planned_lessons', [
            'id' => $lesson->id,
            'teacher_id' => $teacher->id,
        ]);
        $this->assertDatabaseHas('lesson_logs', [
            'id' => $log->id,
            'teacher_id' => $teacher->id,
        ]);
    }

    public function test_inactive_teacher_cannot_open_teacher_workspace(): void
    {
        $user = User::factory()->create(['role' => 'teacher']);
        Teacher::factory()->create([
            'user_id' => $user->id,
            'is_active' => false,
        ]);

        $this
            ->actingAs($user)
            ->get(route('admin.calendar.index'))
            ->assertForbidden();
    }

    public function test_teacher_list_distinguishes_zero_rates_from_missing_rates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Teacher::factory()->create([
            'lesson_price' => null,
            'group_lesson_price' => 0,
            'pair_lesson_price' => 0,
            'trial_lesson_price' => 0,
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.teachers.index'))
            ->assertOk()
            ->assertSee('0.00 грн');
    }

    public function test_admin_cannot_attach_teacher_to_an_account_already_used_by_student(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $studentUser = User::factory()->create(['role' => 'student']);
        Student::factory()->create(['user_id' => $studentUser->id]);

        $this
            ->actingAs($admin)
            ->post(route('admin.teachers.store'), $this->teacherPayload($studentUser))
            ->assertSessionHasErrors(['user_id']);

        $this->assertDatabaseMissing('teachers', ['user_id' => $studentUser->id]);
        $this->assertSame('student', $studentUser->fresh()->role);
    }

    public function test_admin_cannot_create_second_teacher_for_same_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacherUser = User::factory()->create(['role' => 'teacher']);
        Teacher::factory()->create(['user_id' => $teacherUser->id]);

        $this
            ->actingAs($admin)
            ->post(route('admin.teachers.store'), $this->teacherPayload($teacherUser))
            ->assertSessionHasErrors(['user_id']);

        $this->assertSame(1, Teacher::query()->where('user_id', $teacherUser->id)->count());
    }

    public function test_admin_can_have_teacher_profile_without_losing_admin_role(): void
    {
        $actingAdmin = User::factory()->create(['role' => 'admin']);
        $teachingAdmin = User::factory()->create(['role' => 'admin']);

        $this
            ->actingAs($actingAdmin)
            ->post(route('admin.teachers.store'), $this->teacherPayload($teachingAdmin))
            ->assertRedirect(route('admin.teachers.index'));

        $this->assertDatabaseHas('teachers', ['user_id' => $teachingAdmin->id]);
        $this->assertSame('admin', $teachingAdmin->fresh()->role);
    }

    public function test_teacher_account_cannot_be_reassigned_during_profile_update(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->create([
            'user_id' => User::factory()->create(['role' => 'teacher'])->id,
        ]);
        $anotherUser = User::factory()->create(['role' => 'student']);

        $this
            ->actingAs($admin)
            ->put(route('admin.teachers.update', $teacher), array_merge(
                $this->teacherPayload($anotherUser),
                ['first_name' => $teacher->first_name, 'last_name' => $teacher->last_name]
            ))
            ->assertSessionHasErrors(['user_id']);

        $this->assertSame($teacher->user_id, $teacher->fresh()->user_id);
    }

    private function teacherPayload(User $user): array
    {
        return [
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Teacher',
            'is_active' => '1',
            'is_public' => '0',
        ];
    }
}
