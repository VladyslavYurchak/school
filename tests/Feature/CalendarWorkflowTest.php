<?php

namespace Tests\Feature;

use App\Enums\LessonLogStatus;
use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Models\Group;
use App\Models\LessonLog;
use App\Models\PlannedLesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_calendar_only_returns_authenticated_teachers_lessons(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();
        [, $otherTeacher] = $this->createTeacherUser();

        $ownLesson = PlannedLesson::factory()->create([
            'teacher_id' => $teacher->id,
            'start_date' => '2026-06-10 10:00:00',
            'end_date' => '2026-06-10 11:00:00',
        ]);

        $foreignLesson = PlannedLesson::factory()->create([
            'teacher_id' => $otherTeacher->id,
            'start_date' => '2026-06-10 12:00:00',
            'end_date' => '2026-06-10 13:00:00',
        ]);

        $response = $this
            ->actingAs($teacherUser)
            ->getJson(route('admin.calendar.events', [
                'start' => '2026-06-10',
                'end' => '2026-06-11',
            ]));

        $response->assertOk();

        $lessonIds = collect($response->json())->pluck('id');

        $this->assertTrue($lessonIds->contains($ownLesson->id));
        $this->assertFalse($lessonIds->contains($foreignLesson->id));
    }

    public function test_group_attendance_creates_and_updates_one_log_per_group_student(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser([
            'group_lesson_price' => 900,
        ]);

        $group = Group::factory()->group()->create([
            'teacher_id' => $teacher->id,
        ]);

        $students = Student::factory()
            ->count(3)
            ->sequence(
                ['teacher_id' => $teacher->id, 'group_id' => $group->id],
                ['teacher_id' => $teacher->id, 'group_id' => $group->id],
                ['teacher_id' => $teacher->id, 'group_id' => $group->id],
            )
            ->create();

        $lesson = PlannedLesson::factory()->group()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
            'start_date' => '2026-06-10 10:00:00',
            'end_date' => '2026-06-10 11:00:00',
            'status' => LessonStatus::Planned,
            'lesson_type' => LessonType::Group,
        ]);

        $payload = [
            'group_id' => $group->id,
            'lesson_id' => $lesson->id,
            'date' => '2026-06-10',
            'time' => '10:00',
            'present_students' => [
                $students[0]->id,
                $students[2]->id,
            ],
        ];

        $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.group-attendance'), $payload)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(LessonStatus::Completed, $lesson->fresh()->status);
        $this->assertSame(3, LessonLog::where('lesson_id', $lesson->id)->count());
        $this->assertLessonLogStatus($lesson, $students[0], LessonLogStatus::Completed);
        $this->assertLessonLogStatus($lesson, $students[1], LessonLogStatus::Charged);
        $this->assertLessonLogStatus($lesson, $students[2], LessonLogStatus::Completed);

        $payload['present_students'] = [$students[1]->id];

        $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.group-attendance'), $payload)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(3, LessonLog::where('lesson_id', $lesson->id)->count());
        $this->assertLessonLogStatus($lesson, $students[0], LessonLogStatus::Charged);
        $this->assertLessonLogStatus($lesson, $students[1], LessonLogStatus::Completed);
        $this->assertLessonLogStatus($lesson, $students[2], LessonLogStatus::Charged);

        $this->assertEquals(900.0, (float) LessonLog::where('lesson_id', $lesson->id)->sum('teacher_payout_amount'));
        $this->assertDatabaseHas('lesson_logs', [
            'lesson_id' => $lesson->id,
            'teacher_rate_amount_at_charge' => 900,
            'teacher_payout_basis' => 'per_lesson',
        ]);
    }

    public function test_group_attendance_uses_lesson_datetime_instead_of_submitted_datetime(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();
        $group = Group::factory()->group()->create(['teacher_id' => $teacher->id]);
        $student = Student::factory()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
        ]);
        $lesson = PlannedLesson::factory()->group()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
            'start_date' => '2026-06-30 18:15:00',
            'end_date' => '2026-06-30 19:15:00',
        ]);

        $this->actingAs($teacherUser)
            ->postJson(route('admin.calendar.group-attendance'), [
                'group_id' => $group->id,
                'lesson_id' => $lesson->id,
                'date' => '2026-07-01',
                'time' => '01:00',
                'present_students' => [$student->id],
            ])
            ->assertOk();

        $this->assertDatabaseHas('lesson_logs', [
            'lesson_id' => $lesson->id,
            'student_id' => $student->id,
            'date' => '2026-06-30 00:00:00',
            'time' => '18:15:00',
        ]);
    }

    public function test_group_attendance_rejects_student_from_another_group(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();
        $group = Group::factory()->group()->create(['teacher_id' => $teacher->id]);
        $otherGroup = Group::factory()->group()->create(['teacher_id' => $teacher->id]);
        Student::factory()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
        ]);
        $foreignStudent = Student::factory()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $otherGroup->id,
        ]);
        $lesson = PlannedLesson::factory()->group()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
        ]);

        $this->actingAs($teacherUser)
            ->postJson(route('admin.calendar.group-attendance'), [
                'group_id' => $group->id,
                'lesson_id' => $lesson->id,
                'date' => $lesson->start_date->toDateString(),
                'time' => $lesson->start_date->format('H:i'),
                'present_students' => [$foreignStudent->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('present_students');

        $this->assertDatabaseCount('lesson_logs', 0);
        $this->assertSame(LessonStatus::Planned, $lesson->fresh()->status);
    }

    public function test_pair_attendance_uses_pair_rate_and_splits_it_between_students(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser([
            'group_lesson_price' => 900,
            'pair_lesson_price' => 700,
        ]);

        $pair = Group::factory()->pair()->create([
            'teacher_id' => $teacher->id,
        ]);

        $students = Student::factory()
            ->count(2)
            ->sequence(
                ['teacher_id' => $teacher->id, 'group_id' => $pair->id],
                ['teacher_id' => $teacher->id, 'group_id' => $pair->id],
            )
            ->create();

        $lesson = PlannedLesson::factory()->pair()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $pair->id,
            'start_date' => '2026-06-10 12:00:00',
            'end_date' => '2026-06-10 13:00:00',
            'status' => LessonStatus::Planned,
            'lesson_type' => LessonType::Pair,
        ]);

        $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.group-attendance'), [
                'group_id' => $pair->id,
                'lesson_id' => $lesson->id,
                'date' => '2026-06-10',
                'time' => '12:00',
                'present_students' => [$students[0]->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $logs = LessonLog::where('lesson_id', $lesson->id)->orderBy('student_id')->get();

        $this->assertCount(2, $logs);
        $this->assertEquals(700.0, (float) $logs->sum('teacher_payout_amount'));
        $this->assertEquals(350.0, (float) $logs[0]->teacher_payout_amount);
        $this->assertEquals(350.0, (float) $logs[1]->teacher_payout_amount);
        $this->assertTrue($logs->every(fn (LessonLog $log) => $log->lesson_type === LessonType::Pair->value));
        $this->assertTrue($logs->every(fn (LessonLog $log) => (float) $log->teacher_rate_amount_at_charge === 700.0));
    }

    public function test_group_reschedule_preserves_original_duration_and_soft_deletes_old_lesson(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();

        $group = Group::factory()->group()->create([
            'teacher_id' => $teacher->id,
        ]);

        $lesson = PlannedLesson::factory()->group()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
            'start_date' => '2026-06-10 09:00:00',
            'end_date' => '2026-06-10 10:30:00',
            'status' => LessonStatus::Planned,
            'lesson_type' => LessonType::Group,
        ]);

        LessonLog::factory()->create([
            'lesson_id' => $lesson->id,
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
        ]);

        $response = $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.group-lessons.reschedule', ['id' => $lesson->id]), [
                'group_id' => $group->id,
                'lesson_id' => $lesson->id,
                'new_date' => '2026-06-12',
                'new_time' => '15:30',
            ]);

        $response
            ->assertOk()
            ->assertJson(['success' => true]);

        $oldLesson = PlannedLesson::withTrashed()->findOrFail($lesson->id);
        $newLesson = PlannedLesson::findOrFail($response->json('meta.new_lesson_id'));

        $this->assertSoftDeleted('planned_lessons', ['id' => $lesson->id]);
        $this->assertSame(LessonStatus::Rescheduled, $oldLesson->status);
        $this->assertSame('2026-06-12 15:30:00', $newLesson->start_date->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-12 17:00:00', $newLesson->end_date->format('Y-m-d H:i:s'));
        $this->assertSame(90, $newLesson->duration);
        $this->assertSame(0, LessonLog::where('lesson_id', $lesson->id)->count());
    }

    public function test_group_reschedule_rejects_time_already_occupied_by_teacher(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();

        $group = Group::factory()->group()->create([
            'teacher_id' => $teacher->id,
        ]);

        $lesson = PlannedLesson::factory()->group()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
            'start_date' => '2026-06-10 09:00:00',
            'end_date' => '2026-06-10 10:00:00',
            'status' => LessonStatus::Planned,
            'lesson_type' => LessonType::Group,
        ]);

        PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'start_date' => '2026-06-12 15:00:00',
            'end_date' => '2026-06-12 16:00:00',
            'status' => LessonStatus::Planned,
            'lesson_type' => LessonType::Individual,
        ]);

        $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.group-lessons.reschedule', ['id' => $lesson->id]), [
                'group_id' => $group->id,
                'lesson_id' => $lesson->id,
                'new_date' => '2026-06-12',
                'new_time' => '15:30',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['new_date']);

        $this->assertSame(LessonStatus::Planned, $lesson->fresh()->status);
        $this->assertDatabaseCount('planned_lessons', 2);
    }

    public function test_group_reschedule_rejects_mismatched_route_and_body_lesson_ids(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();

        $group = Group::factory()->group()->create([
            'teacher_id' => $teacher->id,
        ]);

        $routeLesson = PlannedLesson::factory()->group()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
            'start_date' => '2026-06-10 09:00:00',
            'end_date' => '2026-06-10 10:00:00',
            'status' => LessonStatus::Planned,
            'lesson_type' => LessonType::Group,
        ]);

        $bodyLesson = PlannedLesson::factory()->group()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
            'start_date' => '2026-06-11 09:00:00',
            'end_date' => '2026-06-11 10:00:00',
            'status' => LessonStatus::Planned,
            'lesson_type' => LessonType::Group,
        ]);

        $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.group-lessons.reschedule', ['id' => $routeLesson->id]), [
                'group_id' => $group->id,
                'lesson_id' => $bodyLesson->id,
                'new_date' => '2026-06-12',
                'new_time' => '15:30',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lesson_id']);

        $this->assertSame(LessonStatus::Planned, $routeLesson->fresh()->status);
        $this->assertSame(LessonStatus::Planned, $bodyLesson->fresh()->status);
    }

    public function test_group_cancel_soft_deletes_target_lesson_and_deletes_only_its_logs(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();

        $group = Group::factory()->group()->create([
            'teacher_id' => $teacher->id,
        ]);

        $targetLesson = PlannedLesson::factory()->group()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
            'start_date' => '2026-06-10 09:00:00',
            'end_date' => '2026-06-10 10:00:00',
            'status' => LessonStatus::Planned,
            'lesson_type' => LessonType::Group,
        ]);

        $otherLesson = PlannedLesson::factory()->group()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
            'start_date' => '2026-06-11 09:00:00',
            'end_date' => '2026-06-11 10:00:00',
            'status' => LessonStatus::Planned,
            'lesson_type' => LessonType::Group,
        ]);

        LessonLog::factory()->count(2)->create([
            'lesson_id' => $targetLesson->id,
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
        ]);
        LessonLog::factory()->create([
            'lesson_id' => $otherLesson->id,
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
        ]);

        $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.group-lessons.cancel', ['id' => $targetLesson->id]), [
                'group_id' => $group->id,
                'lesson_id' => $targetLesson->id,
                'date' => '2026-06-10',
                'time' => '09:00',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $targetLesson->id,
                    'status' => LessonStatus::Cancelled->value,
                ],
                'meta' => [
                    'deleted_logs' => 2,
                ],
            ]);

        $cancelledLesson = PlannedLesson::withTrashed()->findOrFail($targetLesson->id);

        $this->assertSoftDeleted('planned_lessons', ['id' => $targetLesson->id]);
        $this->assertSame(LessonStatus::Cancelled, $cancelledLesson->status);
        $this->assertSame(0, LessonLog::where('lesson_id', $targetLesson->id)->count());
        $this->assertSame(1, LessonLog::where('lesson_id', $otherLesson->id)->count());
        $this->assertNotSoftDeleted('planned_lessons', ['id' => $otherLesson->id]);
    }

    public function test_group_cancel_rejects_mismatched_route_and_body_lesson_ids(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();

        $group = Group::factory()->group()->create([
            'teacher_id' => $teacher->id,
        ]);

        $routeLesson = PlannedLesson::factory()->group()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
            'start_date' => '2026-06-10 09:00:00',
            'end_date' => '2026-06-10 10:00:00',
            'status' => LessonStatus::Planned,
            'lesson_type' => LessonType::Group,
        ]);

        $bodyLesson = PlannedLesson::factory()->group()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
            'start_date' => '2026-06-11 09:00:00',
            'end_date' => '2026-06-11 10:00:00',
            'status' => LessonStatus::Planned,
            'lesson_type' => LessonType::Group,
        ]);

        $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.group-lessons.cancel', ['id' => $routeLesson->id]), [
                'group_id' => $group->id,
                'lesson_id' => $bodyLesson->id,
                'date' => '2026-06-11',
                'time' => '09:00',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lesson_id']);

        $this->assertSame(LessonStatus::Planned, $routeLesson->fresh()->status);
        $this->assertSame(LessonStatus::Planned, $bodyLesson->fresh()->status);
    }

    public function test_teacher_can_create_individual_lesson_for_their_student(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();

        $student = Student::factory()->create([
            'teacher_id' => $teacher->id,
        ]);

        $response = $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.store'), [
                'start' => '2026-06-10 14:00:00',
                'end' => '2026-06-10 15:30:00',
                'duration' => 90,
                'lesson_type' => LessonType::Individual->value,
                'student_id' => $student->id,
                'notes' => 'Focus on speaking',
            ]);

        $response
            ->assertOk()
            ->assertJson(['success' => true]);

        $lesson = PlannedLesson::findOrFail($response->json('event.id'));

        $this->assertSame($teacher->id, $lesson->teacher_id);
        $this->assertSame($student->id, $lesson->student_id);
        $this->assertNull($lesson->group_id);
        $this->assertSame(LessonType::Individual, $lesson->lesson_type);
        $this->assertSame(LessonStatus::Planned, $lesson->status);
        $this->assertSame(90, $lesson->duration);
    }

    public function test_teacher_can_create_group_lesson_only_for_their_group_with_students(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();

        $group = Group::factory()->group()->create([
            'teacher_id' => $teacher->id,
        ]);

        Student::factory()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
        ]);

        $response = $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.store'), [
                'start' => '2026-06-10 16:00:00',
                'duration' => 60,
                'lesson_type' => LessonType::Group->value,
                'group_id' => $group->id,
            ]);

        $response
            ->assertOk()
            ->assertJson(['success' => true]);

        $lesson = PlannedLesson::findOrFail($response->json('event.id'));

        $this->assertSame($teacher->id, $lesson->teacher_id);
        $this->assertSame($group->id, $lesson->group_id);
        $this->assertNull($lesson->student_id);
        $this->assertSame(LessonType::Group, $lesson->lesson_type);
    }

    public function test_teacher_cannot_create_overlapping_lesson_for_same_teacher(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();

        $student = Student::factory()->create([
            'teacher_id' => $teacher->id,
        ]);

        PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'start_date' => '2026-06-10 10:00:00',
            'end_date' => '2026-06-10 11:00:00',
            'status' => LessonStatus::Planned,
        ]);

        $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.store'), [
                'start' => '2026-06-10 10:30:00',
                'duration' => 60,
                'lesson_type' => LessonType::Individual->value,
                'student_id' => $student->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start']);

        $this->assertSame(1, PlannedLesson::where('teacher_id', $teacher->id)->count());
    }

    public function test_teacher_can_update_only_time_for_their_own_lesson(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();

        $student = Student::factory()->create([
            'teacher_id' => $teacher->id,
        ]);

        $lesson = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'title' => 'Original lesson',
            'notes' => 'Original notes',
            'start_date' => '2026-06-10 10:00:00',
            'end_date' => '2026-06-10 11:00:00',
            'lesson_type' => LessonType::Individual,
        ]);

        $this
            ->actingAs($teacherUser)
            ->putJson(route('admin.calendar.events.update', ['id' => $lesson->id]), [
                'title' => 'Updated lesson',
                'date' => '2026-06-11',
                'time' => '12:30',
                'duration' => 45,
                'lesson_type' => LessonType::Individual->value,
                'student_id' => $student->id,
                'notes' => 'Updated notes',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $lesson->refresh();

        $this->assertSame('Original lesson', $lesson->title);
        $this->assertSame('2026-06-11 12:30:00', $lesson->start_date->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-11 13:15:00', $lesson->end_date->format('Y-m-d H:i:s'));
        $this->assertSame($student->id, $lesson->student_id);
        $this->assertNull($lesson->group_id);
        $this->assertSame(LessonType::Individual, $lesson->lesson_type);
        $this->assertSame('Original notes', $lesson->notes);
    }

    public function test_teacher_cannot_update_lesson_to_overlap_their_other_lesson(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();

        $student = Student::factory()->create([
            'teacher_id' => $teacher->id,
        ]);

        PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'start_date' => '2026-06-10 10:00:00',
            'end_date' => '2026-06-10 11:00:00',
            'status' => LessonStatus::Planned,
        ]);

        $lessonToMove = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'start_date' => '2026-06-10 13:00:00',
            'end_date' => '2026-06-10 14:00:00',
            'status' => LessonStatus::Planned,
        ]);

        $this
            ->actingAs($teacherUser)
            ->putJson(route('admin.calendar.events.update', ['id' => $lessonToMove->id]), [
                'title' => $lessonToMove->title,
                'date' => '2026-06-10',
                'time' => '10:30',
                'duration' => 60,
                'lesson_type' => LessonType::Individual->value,
                'student_id' => $student->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date']);

        $lessonToMove->refresh();

        $this->assertSame('2026-06-10 13:00:00', $lessonToMove->start_date->format('Y-m-d H:i:s'));
    }

    public function test_completing_individual_lesson_marks_lesson_and_creates_single_log(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser([
            'lesson_price' => 700,
        ]);

        $student = Student::factory()->create([
            'teacher_id' => $teacher->id,
        ]);

        $lesson = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'start_date' => '2026-06-10 10:00:00',
            'end_date' => '2026-06-10 11:00:00',
            'status' => LessonStatus::Planned,
            'lesson_type' => LessonType::Individual,
        ]);

        $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.events.complete', ['id' => $lesson->id]))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.events.complete', ['id' => $lesson->id]))
            ->assertOk()
            ->assertJson(['success' => false]);

        $this->assertSame(LessonStatus::Completed, $lesson->fresh()->status);
        $this->assertSame(1, LessonLog::where('lesson_id', $lesson->id)->count());
        $this->assertDatabaseHas('lesson_logs', [
            'lesson_id' => $lesson->id,
            'student_id' => $student->id,
            'status' => LessonLogStatus::Completed->value,
            'duration' => 60,
        ]);
    }

    public function test_cancelling_individual_lesson_soft_deletes_lesson_and_removes_logs(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();

        $student = Student::factory()->create([
            'teacher_id' => $teacher->id,
        ]);

        $lesson = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'start_date' => '2026-06-10 10:00:00',
            'end_date' => '2026-06-10 11:00:00',
            'status' => LessonStatus::Planned,
            'lesson_type' => LessonType::Individual,
        ]);

        LessonLog::factory()->count(2)->create([
            'lesson_id' => $lesson->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
        ]);

        $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.events.cancel', ['id' => $lesson->id]))
            ->assertOk()
            ->assertJson(['success' => true]);

        $cancelledLesson = PlannedLesson::withTrashed()->findOrFail($lesson->id);

        $this->assertSoftDeleted('planned_lessons', ['id' => $lesson->id]);
        $this->assertSame(LessonStatus::Cancelled, $cancelledLesson->status);
        $this->assertSame(0, LessonLog::where('lesson_id', $lesson->id)->count());
    }

    public function test_teacher_can_cancel_selected_and_all_future_individual_lessons_for_student(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();
        $student = Student::factory()->create(['teacher_id' => $teacher->id]);
        $otherStudent = Student::factory()->create(['teacher_id' => $teacher->id]);

        $earlierLesson = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'start_date' => '2026-06-08 10:00:00',
            'end_date' => '2026-06-08 11:00:00',
            'status' => LessonStatus::Planned,
        ]);
        $selectedLesson = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'start_date' => '2026-06-10 10:00:00',
            'end_date' => '2026-06-10 11:00:00',
            'status' => LessonStatus::Planned,
        ]);
        $laterLesson = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'start_date' => '2026-06-18 15:00:00',
            'end_date' => '2026-06-18 16:00:00',
            'status' => LessonStatus::Planned,
        ]);
        $completedLesson = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'start_date' => '2026-06-20 10:00:00',
            'end_date' => '2026-06-20 11:00:00',
            'status' => LessonStatus::Completed,
        ]);
        $otherStudentLesson = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $otherStudent->id,
            'start_date' => '2026-06-18 17:00:00',
            'end_date' => '2026-06-18 18:00:00',
            'status' => LessonStatus::Planned,
        ]);

        LessonLog::factory()->create([
            'lesson_id' => $laterLesson->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
        ]);

        $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.events.cancel', ['id' => $selectedLesson->id]), [
                'scope' => 'student_future',
            ])
            ->assertOk()
            ->assertJsonPath('meta.cancelled_count', 2);

        $this->assertSoftDeleted('planned_lessons', ['id' => $selectedLesson->id]);
        $this->assertSoftDeleted('planned_lessons', ['id' => $laterLesson->id]);
        $this->assertDatabaseHas('planned_lessons', [
            'id' => $earlierLesson->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('planned_lessons', [
            'id' => $completedLesson->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('planned_lessons', [
            'id' => $otherStudentLesson->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseMissing('lesson_logs', ['lesson_id' => $laterLesson->id]);
        $this->assertDatabaseHas('lesson_actions', [
            'lesson_id' => $selectedLesson->id,
            'action' => 'cancelled',
        ]);
        $this->assertDatabaseHas('lesson_actions', [
            'lesson_id' => $laterLesson->id,
            'action' => 'cancelled',
        ]);
    }

    public function test_mass_cancellation_rejects_trial_without_student(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();
        $trial = PlannedLesson::factory()->create([
            'teacher_id' => $teacher->id,
            'student_id' => null,
            'group_id' => null,
            'lesson_type' => LessonType::Trial,
            'status' => LessonStatus::Planned,
        ]);

        $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.events.cancel', ['id' => $trial->id]), [
                'scope' => 'student_future',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('planned_lessons', [
            'id' => $trial->id,
            'deleted_at' => null,
        ]);
    }

    public function test_rescheduling_individual_lesson_preserves_duration_and_removes_old_logs(): void
    {
        [$teacherUser, $teacher] = $this->createTeacherUser();

        $student = Student::factory()->create([
            'teacher_id' => $teacher->id,
        ]);

        $lesson = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'start_date' => '2026-06-10 10:00:00',
            'end_date' => '2026-06-10 11:30:00',
            'status' => LessonStatus::Planned,
            'lesson_type' => LessonType::Individual,
        ]);

        LessonLog::factory()->create([
            'lesson_id' => $lesson->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
        ]);

        $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.events.reschedule', ['id' => $lesson->id]), [
                'new_date' => '2026-06-12',
                'new_time' => '15:00',
                'initiator' => 'teacher',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $oldLesson = PlannedLesson::withTrashed()->findOrFail($lesson->id);
        $newLesson = PlannedLesson::where('student_id', $student->id)
            ->where('id', '!=', $lesson->id)
            ->firstOrFail();

        $this->assertSoftDeleted('planned_lessons', ['id' => $lesson->id]);
        $this->assertSame(LessonStatus::Rescheduled, $oldLesson->status);
        $this->assertSame('2026-06-12 15:00:00', $newLesson->start_date->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-12 16:30:00', $newLesson->end_date->format('Y-m-d H:i:s'));
        $this->assertSame(90, $newLesson->duration);
        $this->assertSame(0, LessonLog::where('lesson_id', $lesson->id)->count());
    }

    public function test_teacher_cannot_mutate_another_teachers_lesson(): void
    {
        [$teacherUser] = $this->createTeacherUser();
        [, $otherTeacher] = $this->createTeacherUser();

        $foreignStudent = Student::factory()->create([
            'teacher_id' => $otherTeacher->id,
        ]);

        $foreignLesson = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $otherTeacher->id,
            'student_id' => $foreignStudent->id,
            'start_date' => '2026-06-10 10:00:00',
            'end_date' => '2026-06-10 11:00:00',
            'status' => LessonStatus::Planned,
            'lesson_type' => LessonType::Individual,
        ]);

        $this
            ->actingAs($teacherUser)
            ->putJson(route('admin.calendar.events.update', ['id' => $foreignLesson->id]), [
                'title' => 'Bad update',
                'date' => '2026-06-11',
                'time' => '12:00',
                'duration' => 60,
                'lesson_type' => LessonType::Individual->value,
                'student_id' => $foreignStudent->id,
            ])
            ->assertNotFound();

        $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.events.complete', ['id' => $foreignLesson->id]))
            ->assertNotFound();

        $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.events.cancel', ['id' => $foreignLesson->id]))
            ->assertNotFound();

        $this
            ->actingAs($teacherUser)
            ->postJson(route('admin.calendar.events.reschedule', ['id' => $foreignLesson->id]), [
                'new_date' => '2026-06-12',
                'new_time' => '15:00',
                'initiator' => 'teacher',
            ])
            ->assertNotFound();

        $foreignLesson->refresh();

        $this->assertSame(LessonStatus::Planned, $foreignLesson->status);
        $this->assertSame('2026-06-10 10:00:00', $foreignLesson->start_date->format('Y-m-d H:i:s'));
    }

    public function test_admin_teacher_calendar_events_show_all_teachers_and_can_filter_by_teacher(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [, $firstTeacher] = $this->createTeacherUser();
        [, $secondTeacher] = $this->createTeacherUser();

        $firstLesson = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $firstTeacher->id,
            'start_date' => '2026-06-10 10:00:00',
            'end_date' => '2026-06-10 11:00:00',
            'status' => LessonStatus::Planned,
        ]);

        $secondLesson = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $secondTeacher->id,
            'start_date' => '2026-06-10 12:00:00',
            'end_date' => '2026-06-10 13:00:00',
            'status' => LessonStatus::Completed,
        ]);

        $response = $this
            ->actingAs($admin)
            ->getJson(route('admin.calendar_teachers.teachers.events', [
                'start' => '2026-06-10',
                'end' => '2026-06-11',
            ]));

        $response->assertOk();

        $lessonIds = collect($response->json())->pluck('id');

        $this->assertTrue($lessonIds->contains($firstLesson->id));
        $this->assertTrue($lessonIds->contains($secondLesson->id));

        $filteredResponse = $this
            ->actingAs($admin)
            ->getJson(route('admin.calendar_teachers.teachers.events', [
                'start' => '2026-06-10',
                'end' => '2026-06-11',
                'teacher_id' => $firstTeacher->id,
            ]));

        $filteredResponse->assertOk();

        $filteredIds = collect($filteredResponse->json())->pluck('id');

        $this->assertTrue($filteredIds->contains($firstLesson->id));
        $this->assertFalse($filteredIds->contains($secondLesson->id));
    }

    private function createTeacherUser(array $teacherOverrides = []): array
    {
        $user = User::factory()->create(['role' => 'teacher']);

        $teacher = Teacher::factory()->create(array_merge([
            'user_id' => $user->id,
        ], $teacherOverrides));

        return [$user, $teacher];
    }

    private function assertLessonLogStatus(PlannedLesson $lesson, Student $student, LessonLogStatus $status): void
    {
        $this->assertDatabaseHas('lesson_logs', [
            'lesson_id' => $lesson->id,
            'student_id' => $student->id,
            'status' => $status->value,
        ]);
    }
}
