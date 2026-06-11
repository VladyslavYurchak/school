<?php

namespace Tests\Unit;

use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Models\Group;
use App\Models\PlannedLesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\Calendar\CalendarAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarAvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_overlap_detects_real_overlap_but_not_touching_edges(): void
    {
        $teacher = Teacher::factory()->create();

        PlannedLesson::factory()->create([
            'teacher_id' => $teacher->id,
            'start_date' => '2026-06-11 10:00:00',
            'end_date' => '2026-06-11 11:00:00',
            'status' => LessonStatus::Planned,
        ]);

        $service = app(CalendarAvailabilityService::class);

        $this->assertTrue($service->teacherHasOverlap(
            $teacher->id,
            CarbonImmutable::parse('2026-06-11 10:30:00'),
            CarbonImmutable::parse('2026-06-11 11:30:00')
        ));

        $this->assertFalse($service->teacherHasOverlap(
            $teacher->id,
            CarbonImmutable::parse('2026-06-11 11:00:00'),
            CarbonImmutable::parse('2026-06-11 12:00:00')
        ));
    }

    public function test_teacher_overlap_ignores_cancelled_rescheduled_and_excepted_lesson(): void
    {
        $teacher = Teacher::factory()->create();

        $currentLesson = PlannedLesson::factory()->create([
            'teacher_id' => $teacher->id,
            'start_date' => '2026-06-11 10:00:00',
            'end_date' => '2026-06-11 11:00:00',
            'status' => LessonStatus::Planned,
        ]);

        PlannedLesson::factory()->create([
            'teacher_id' => $teacher->id,
            'start_date' => '2026-06-11 10:15:00',
            'end_date' => '2026-06-11 10:45:00',
            'status' => LessonStatus::Cancelled,
        ]);

        PlannedLesson::factory()->create([
            'teacher_id' => $teacher->id,
            'start_date' => '2026-06-11 10:15:00',
            'end_date' => '2026-06-11 10:45:00',
            'status' => LessonStatus::Rescheduled,
        ]);

        $service = app(CalendarAvailabilityService::class);

        $this->assertFalse($service->teacherHasOverlap(
            $teacher->id,
            CarbonImmutable::parse('2026-06-11 10:15:00'),
            CarbonImmutable::parse('2026-06-11 10:45:00'),
            $currentLesson->id
        ));
    }

    public function test_student_overlap_uses_student_schedule(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->create(['teacher_id' => $teacher->id]);
        $otherStudent = Student::factory()->create(['teacher_id' => $teacher->id]);

        PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'start_date' => '2026-06-11 15:00:00',
            'end_date' => '2026-06-11 16:00:00',
            'status' => LessonStatus::Planned,
        ]);

        $service = app(CalendarAvailabilityService::class);

        $this->assertTrue($service->studentHasOverlap(
            $student->id,
            CarbonImmutable::parse('2026-06-11 15:30:00'),
            CarbonImmutable::parse('2026-06-11 16:30:00')
        ));

        $this->assertFalse($service->studentHasOverlap(
            $otherStudent->id,
            CarbonImmutable::parse('2026-06-11 15:30:00'),
            CarbonImmutable::parse('2026-06-11 16:30:00')
        ));
    }

    public function test_group_overlap_uses_group_schedule(): void
    {
        $teacher = Teacher::factory()->create();
        $group = Group::factory()->group()->create(['teacher_id' => $teacher->id]);
        $otherGroup = Group::factory()->group()->create(['teacher_id' => $teacher->id]);

        PlannedLesson::factory()->group()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
            'lesson_type' => LessonType::Group,
            'start_date' => '2026-06-11 18:00:00',
            'end_date' => '2026-06-11 19:00:00',
            'status' => LessonStatus::Planned,
        ]);

        $service = app(CalendarAvailabilityService::class);

        $this->assertTrue($service->groupHasOverlap(
            $group->id,
            CarbonImmutable::parse('2026-06-11 18:30:00'),
            CarbonImmutable::parse('2026-06-11 19:30:00')
        ));

        $this->assertFalse($service->groupHasOverlap(
            $otherGroup->id,
            CarbonImmutable::parse('2026-06-11 18:30:00'),
            CarbonImmutable::parse('2026-06-11 19:30:00')
        ));
    }
}
