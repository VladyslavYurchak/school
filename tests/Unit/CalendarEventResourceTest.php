<?php

namespace Tests\Unit;

use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Http\Resources\CalendarEventResource;
use App\Models\Group;
use App\Models\PlannedLesson;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarEventResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_individual_lesson_with_student_title_and_basic_fields(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->create([
            'teacher_id' => $teacher->id,
            'first_name' => 'Іван',
            'last_name'  => 'Петров',
        ]);

        $lesson = PlannedLesson::factory()->create([
            'teacher_id'  => $teacher->id,
            'student_id'  => $student->id,
            'group_id'    => null,
            'title'       => 'Якийсь заголовок',
            'lesson_type' => LessonType::Individual,        // enum
            'status'      => LessonStatus::Planned,
            'start_date'  => '2025-01-10 10:00:00',
            'end_date'    => '2025-01-10 11:00:00',
        ]);

        // завантажимо звʼязки, як це робить контролер
        $lesson->load('student', 'group');

        $data = (new CalendarEventResource($lesson))->resolve();

        // базові поля
        $this->assertEquals($lesson->id, $data['id']);
        $this->assertEquals('Петров Іван', $data['title']); // прізвище + імʼя
        $this->assertEquals($lesson->start_date->toIso8601String(), $data['start']);
        $this->assertEquals($lesson->end_date->toIso8601String(), $data['end']);
        $this->assertFalse($data['allDay']);

        // колір для Planned
        $this->assertEquals('#6c757d', $data['backgroundColor']);

        // extendedProps
        $this->assertEquals($lesson->id, $data['extendedProps']['lesson_id']);
        $this->assertEquals(LessonType::Individual->value, $data['extendedProps']['lesson_type']);
        $this->assertEquals(LessonStatus::Planned->value, $data['extendedProps']['status']);
        $this->assertNull($data['extendedProps']['group_id']);
        $this->assertIsArray($data['extendedProps']['members']);
        $this->assertCount(0, $data['extendedProps']['members']);
    }

    public function test_trial_lesson_has_prefix_and_green_color_when_completed(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->create([
            'teacher_id' => $teacher->id,
            'first_name' => 'Оля',
            'last_name'  => 'Коваль',
        ]);

        $lesson = PlannedLesson::factory()->create([
            'teacher_id'  => $teacher->id,
            'student_id'  => $student->id,
            'lesson_type' => LessonType::Trial,
            'status'      => LessonStatus::Completed,
            'start_date'  => '2025-01-15 12:00:00',
            'end_date'    => '2025-01-15 13:00:00',
        ]);

        $lesson->load('student', 'group');

        $data = (new CalendarEventResource($lesson))->resolve();

        $this->assertEquals('Пробне: Коваль Оля', $data['title']);
        $this->assertEquals('#198754', $data['backgroundColor']); // Completed
        $this->assertEquals(LessonType::Trial->value, $data['extendedProps']['lesson_type']);
        $this->assertEquals(LessonStatus::Completed->value, $data['extendedProps']['status']);
    }

    public function test_group_lesson_title_and_members(): void
    {
        $teacher = Teacher::factory()->create();

        $group = Group::factory()->create([
            'teacher_id' => $teacher->id,
            'name'       => 'A1 ранкова',
            'type'       => 'group',
        ]);

        $student1 = Student::factory()->create([
            'teacher_id' => $teacher->id,
            'group_id'   => $group->id,
            'first_name' => 'Марія',
            'last_name'  => 'Сидоренко',
        ]);

        $student2 = Student::factory()->create([
            'teacher_id' => $teacher->id,
            'group_id'   => $group->id,
            'first_name' => 'Ігор',
            'last_name'  => 'Лисенко',
        ]);

        $lesson = PlannedLesson::factory()->create([
            'teacher_id'  => $teacher->id,
            'group_id'    => $group->id,
            'student_id'  => null,
            'lesson_type' => LessonType::Group,
            'status'      => LessonStatus::Planned,
            'start_date'  => '2025-01-20 18:00:00',
            'end_date'    => '2025-01-20 19:00:00',
        ]);

        // важливо: завантажити group.students, бо ресурс перевіряє relationLoaded('students')
        $lesson->load(['group.students', 'student']);

        $data = (new CalendarEventResource($lesson))->resolve();

        // title для групового
        $this->assertEquals('Група: A1 ранкова', $data['title']);
        $this->assertEquals($group->id, $data['extendedProps']['group_id']);

        $members = $data['extendedProps']['members'];
        $this->assertCount(2, $members);

        $this->assertEquals($student1->id, $members[0]['id']);
        $this->assertEquals('Марія Сидоренко', $members[0]['name']);

        $this->assertEquals($student2->id, $members[1]['id']);
        $this->assertEquals('Ігор Лисенко', $members[1]['name']);
    }
}
