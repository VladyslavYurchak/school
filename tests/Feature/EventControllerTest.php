<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\PlannedLesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Щоб не заважав middleware 'teacher'
        $this->withoutMiddleware();
    }

    public function test_returns_empty_collection_if_user_has_no_teacher(): void
    {
        // користувач, який проходить middleware, але без повʼязаного Teacher
        $user = User::factory()->create([
            'role' => 'teacher',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('admin.calendar.events', [
                'start' => '2025-01-01',
                'end'   => '2025-01-31',
            ]));

        $response->assertOk();

        // Контролер повертає просто collection(), тобто []
        $this->assertSame([], $response->json());
    }

    public function test_returns_lessons_for_teacher_within_date_range(): void
    {
        $teacherUser = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::factory()->create([
            'user_id' => $teacherUser->id,
        ]);

        $student = Student::factory()->create([
            'teacher_id' => $teacher->id,
        ]);

        // Урок у діапазоні — має прийти
        $inRangeLesson = PlannedLesson::factory()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'start_date' => '2025-01-10 10:00:00',
            'end_date'   => '2025-01-10 11:00:00',
        ]);

        // Урок іншого вчителя — не має прийти
        $otherTeacher = Teacher::factory()->create();
        $otherLesson = PlannedLesson::factory()->create([
            'teacher_id' => $otherTeacher->id,
            'start_date' => '2025-01-10 10:00:00',
            'end_date'   => '2025-01-10 11:00:00',
        ]);

        // Урок цього ж вчителя, але поза діапазоном — теж не має прийти
        $outOfRangeLesson = PlannedLesson::factory()->create([
            'teacher_id' => $teacher->id,
            'start_date' => '2025-02-01 10:00:00',
            'end_date'   => '2025-02-01 11:00:00',
        ]);

        $response = $this
            ->actingAs($teacherUser)
            ->getJson(route('admin.calendar.events', [
                'start' => '2025-01-01',
                'end'   => '2025-01-31',
            ]));

        $response->assertOk();

        $json = $response->json();

        // Має бути наш inRangeLesson
        $this->assertTrue(
            collect($json)->contains(fn ($item) => $item['id'] === $inRangeLesson->id),
            'In-range lesson not found in response'
        );

        // Не має бути урока іншого вчителя
        $this->assertFalse(
            collect($json)->contains(fn ($item) => $item['id'] === $otherLesson->id),
            'Foreign teacher lesson should not be in response'
        );

        // Не має бути урока поза діапазоном
        $this->assertFalse(
            collect($json)->contains(fn ($item) => $item['id'] === $outOfRangeLesson->id),
            'Out-of-range lesson should not be in response'
        );
    }

    public function test_does_not_return_lessons_of_other_teacher(): void
    {
        $teacherUser = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::factory()->create([
            'user_id' => $teacherUser->id,
        ]);

        $student = Student::factory()->create([
            'teacher_id' => $teacher->id,
        ]);

        $ownLesson = PlannedLesson::factory()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'start_date' => '2025-01-10 10:00:00',
            'end_date'   => '2025-01-10 11:00:00',
        ]);

        $otherTeacher = Teacher::factory()->create();
        $foreignLesson = PlannedLesson::factory()->create([
            'teacher_id' => $otherTeacher->id,
            'start_date' => '2025-01-15 10:00:00',
            'end_date'   => '2025-01-15 11:00:00',
        ]);

        $response = $this
            ->actingAs($teacherUser)
            ->getJson(route('admin.calendar.events', [
                'start' => '2025-01-01',
                'end'   => '2025-01-31',
            ]));

        $response->assertOk();
        $json = $response->json();

        $this->assertTrue(
            collect($json)->contains(fn ($item) => $item['id'] === $ownLesson->id),
            'Own lesson not found in response'
        );

        $this->assertFalse(
            collect($json)->contains(fn ($item) => $item['id'] === $foreignLesson->id),
            'Foreign teacher lesson should not be in response'
        );
    }

    public function test_intersects_includes_lessons_overlapping_range(): void
    {
        $teacherUser = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::factory()->create([
            'user_id' => $teacherUser->id,
        ]);

        $start = '2025-01-10 00:00:00';
        $end   = '2025-01-20 23:59:59';

        // 1. Починається раніше, закінчується всередині → має бути
        $overlappingBefore = PlannedLesson::factory()->create([
            'teacher_id' => $teacher->id,
            'start_date' => '2025-01-05 10:00:00',
            'end_date'   => '2025-01-12 11:00:00',
        ]);

        // 2. Всередині діапазону → має бути
        $inside = PlannedLesson::factory()->create([
            'teacher_id' => $teacher->id,
            'start_date' => '2025-01-15 10:00:00',
            'end_date'   => '2025-01-15 11:00:00',
        ]);

        // 3. Повністю після діапазону → не має бути
        $after = PlannedLesson::factory()->create([
            'teacher_id' => $teacher->id,
            'start_date' => '2025-01-25 10:00:00',
            'end_date'   => '2025-01-25 11:00:00',
        ]);

        $response = $this
            ->actingAs($teacherUser)
            ->getJson(route('admin.calendar.events', [
                'start' => $start,
                'end'   => $end,
            ]));

        $response->assertOk();
        $json = $response->json();

        $this->assertTrue(
            collect($json)->contains(fn ($item) => $item['id'] === $overlappingBefore->id),
            'Overlapping-before lesson not found'
        );

        $this->assertTrue(
            collect($json)->contains(fn ($item) => $item['id'] === $inside->id),
            'Inside-range lesson not found'
        );

        $this->assertFalse(
            collect($json)->contains(fn ($item) => $item['id'] === $after->id),
            'After-range lesson should not be returned'
        );
    }
}
