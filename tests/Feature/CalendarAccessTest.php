<?php

namespace Tests\Feature;

use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Models\Group;
use App\Models\PlannedLesson;
use App\Models\Student;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_cannot_load_another_teachers_group_members(): void
    {
        [$teacherUser] = $this->createTeacherUser();
        [, $otherTeacher] = $this->createTeacherUser();

        $foreignGroup = Group::factory()->group()->create([
            'teacher_id' => $otherTeacher->id,
        ]);

        Student::factory()->create([
            'teacher_id' => $otherTeacher->id,
            'group_id' => $foreignGroup->id,
        ]);

        $this
            ->actingAs($teacherUser)
            ->getJson(route('groups.members', ['group' => $foreignGroup->id]))
            ->assertNotFound();
    }

    public function test_teacher_cannot_create_individual_lesson_for_another_teachers_student(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();
        [, $otherTeacher] = $this->createTeacherUser();

        $foreignStudent = Student::factory()->create([
            'teacher_id' => $otherTeacher->id,
        ]);

        $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.store'), [
                'start' => '2026-06-11 10:00:00',
                'duration' => 60,
                'lesson_type' => LessonType::Individual->value,
                'student_id' => $foreignStudent->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['student_id']);

        $this->assertSame(0, PlannedLesson::where('teacher_id', $teacher->id)->count());
    }

    public function test_teacher_cannot_update_their_lesson_to_another_teachers_student(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();
        [, $otherTeacher] = $this->createTeacherUser();

        $ownStudent = Student::factory()->create([
            'teacher_id' => $teacher->id,
        ]);

        $foreignStudent = Student::factory()->create([
            'teacher_id' => $otherTeacher->id,
        ]);

        $lesson = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $ownStudent->id,
            'start_date' => '2026-06-11 10:00:00',
            'end_date' => '2026-06-11 11:00:00',
            'status' => LessonStatus::Planned,
            'lesson_type' => LessonType::Individual,
        ]);

        $this
            ->actingAs($teacherUser)
            ->putJson(route('admin.calendar.events.update', ['id' => $lesson->id]), [
                'title' => $lesson->title,
                'date' => '2026-06-11',
                'time' => '12:00',
                'duration' => 60,
                'lesson_type' => LessonType::Individual->value,
                'student_id' => $foreignStudent->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['student_id']);

        $this->assertSame($ownStudent->id, $lesson->fresh()->student_id);
    }

    public function test_teacher_cannot_update_their_lesson_to_another_teachers_group(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();
        [, $otherTeacher] = $this->createTeacherUser();

        $ownStudent = Student::factory()->create([
            'teacher_id' => $teacher->id,
        ]);

        $foreignGroup = Group::factory()->group()->create([
            'teacher_id' => $otherTeacher->id,
        ]);

        $lesson = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $ownStudent->id,
            'group_id' => null,
            'start_date' => '2026-06-11 10:00:00',
            'end_date' => '2026-06-11 11:00:00',
            'status' => LessonStatus::Planned,
            'lesson_type' => LessonType::Individual,
        ]);

        $this
            ->actingAs($teacherUser)
            ->putJson(route('admin.calendar.events.update', ['id' => $lesson->id]), [
                'title' => $lesson->title,
                'date' => '2026-06-11',
                'time' => '12:00',
                'duration' => 60,
                'lesson_type' => LessonType::Group->value,
                'group_id' => $foreignGroup->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['group_id']);

        $lesson->refresh();

        $this->assertSame($ownStudent->id, $lesson->student_id);
        $this->assertNull($lesson->group_id);
        $this->assertSame(LessonType::Individual, $lesson->lesson_type);
    }

    public function test_teacher_can_create_group_lesson_when_all_students_have_group_subscription(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();
        $groupTemplate = SubscriptionTemplate::factory()->create(['type' => 'group']);

        $group = Group::factory()->group()->create([
            'teacher_id' => $teacher->id,
        ]);

        Student::factory()->count(2)->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
            'subscription_id' => $groupTemplate->id,
        ]);

        $response = $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.store'), [
                'start' => '2026-06-11 10:00:00',
                'duration' => 60,
                'lesson_type' => LessonType::Group->value,
                'group_id' => $group->id,
            ]);

        $response
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_teacher_cannot_create_group_lesson_when_any_student_has_pair_subscription(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();
        $groupTemplate = SubscriptionTemplate::factory()->create(['type' => 'group']);
        $pairTemplate = SubscriptionTemplate::factory()->create(['type' => 'pair']);

        $group = Group::factory()->group()->create([
            'teacher_id' => $teacher->id,
        ]);

        Student::factory()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
            'subscription_id' => $groupTemplate->id,
        ]);

        Student::factory()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
            'subscription_id' => $pairTemplate->id,
        ]);

        $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.store'), [
                'start' => '2026-06-11 10:00:00',
                'duration' => 60,
                'lesson_type' => LessonType::Group->value,
                'group_id' => $group->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['group_id']);
    }

    public function test_teacher_can_create_pair_lesson_when_all_students_have_pair_subscription(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();
        $pairTemplate = SubscriptionTemplate::factory()->create(['type' => 'pair']);

        $pair = Group::factory()->pair()->create([
            'teacher_id' => $teacher->id,
        ]);

        Student::factory()->count(2)->create([
            'teacher_id' => $teacher->id,
            'group_id' => $pair->id,
            'subscription_id' => $pairTemplate->id,
        ]);

        $response = $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.store'), [
                'start' => '2026-06-11 10:00:00',
                'duration' => 60,
                'lesson_type' => LessonType::Pair->value,
                'group_id' => $pair->id,
            ]);

        $response
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_teacher_cannot_create_pair_lesson_when_any_student_has_group_subscription(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();
        $groupTemplate = SubscriptionTemplate::factory()->create(['type' => 'group']);
        $pairTemplate = SubscriptionTemplate::factory()->create(['type' => 'pair']);

        $pair = Group::factory()->pair()->create([
            'teacher_id' => $teacher->id,
        ]);

        Student::factory()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $pair->id,
            'subscription_id' => $pairTemplate->id,
        ]);

        Student::factory()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $pair->id,
            'subscription_id' => $groupTemplate->id,
        ]);

        $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.store'), [
                'start' => '2026-06-11 10:00:00',
                'duration' => 60,
                'lesson_type' => LessonType::Pair->value,
                'group_id' => $pair->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['group_id']);
    }

    private function createTeacherUser(array $teacherOverrides = []): array
    {
        $user = User::factory()->create(['role' => 'teacher']);

        $teacher = Teacher::factory()->create(array_merge([
            'user_id' => $user->id,
        ], $teacherOverrides));

        return [$user, $teacher];
    }
}
