<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseLessonAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_paid_course_lesson(): void
    {
        [$course, $lesson] = $this->createCourseWithLesson(['price' => 500]);

        $this->get(route('courses.lessons.show', [$course, $lesson]))
            ->assertRedirect(route('login'));
    }

    public function test_unpaid_student_is_redirected_from_paid_course_lesson(): void
    {
        [$course, $lesson] = $this->createCourseWithLesson(['price' => 500]);
        $user = User::factory()->create(['role' => 'student']);

        $this->actingAs($user)
            ->get(route('courses.lessons.show', [$course, $lesson]))
            ->assertRedirect(route('courses.show', $course));
    }

    public function test_paid_student_can_open_paid_course_lesson(): void
    {
        [$course, $lesson] = $this->createCourseWithLesson(['price' => 500]);
        $user = User::factory()->create(['role' => 'student']);

        $user->courses()->attach($course->id, [
            'status' => 'paid',
            'paid_amount' => 500,
        ]);

        $this->actingAs($user)
            ->get(route('courses.lessons.show', [$course, $lesson]))
            ->assertOk()
            ->assertViewIs('public.courses.lesson')
            ->assertSee($lesson->title);
    }

    public function test_free_course_lesson_is_publicly_available(): void
    {
        [$course, $lesson] = $this->createCourseWithLesson(['price' => 0]);

        $this->get(route('courses.lessons.show', [$course, $lesson]))
            ->assertOk()
            ->assertViewIs('public.courses.lesson')
            ->assertSee($lesson->title);
    }

    public function test_free_lesson_inside_paid_course_is_publicly_available(): void
    {
        [$course, $lesson] = $this->createCourseWithLesson(['price' => 500], ['price' => 0]);

        $this
            ->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('Відкрити', false);

        $this
            ->get(route('courses.lessons.show', [$course, $lesson]))
            ->assertOk()
            ->assertViewIs('public.courses.lesson')
            ->assertSee($lesson->title);
    }

    public function test_separately_purchased_lesson_does_not_unlock_course_or_sibling_lesson(): void
    {
        [$course, $lesson] = $this->createCourseWithLesson(
            ['price' => 500],
            ['price' => 200]
        );
        $sibling = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Sibling lesson',
            'description' => 'Sibling',
            'content' => 'Sibling content',
            'position' => 2,
            'price' => 200,
            'is_published' => true,
        ]);
        $user = User::factory()->create(['role' => 'student']);
        $user->lessons()->attach($lesson->id, [
            'status' => 'paid',
            'paid_amount' => 200,
        ]);

        $this->actingAs($user)
            ->get(route('courses.lessons.show', [$course, $lesson]))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('courses.lessons.show', [$course, $sibling]))
            ->assertRedirect(route('courses.show', $course));

        $this->assertFalse($course->isAvailableFor($user));
    }

    public function test_refunded_course_and_lesson_do_not_grant_access(): void
    {
        [$course, $lesson] = $this->createCourseWithLesson(
            ['price' => 500],
            ['price' => 200]
        );
        $user = User::factory()->create(['role' => 'student']);
        $user->courses()->attach($course->id, [
            'status' => 'refunded',
            'paid_amount' => 500,
        ]);
        $user->lessons()->attach($lesson->id, [
            'status' => 'refunded',
            'paid_amount' => 200,
        ]);

        $this->actingAs($user)
            ->get(route('courses.lessons.show', [$course, $lesson]))
            ->assertRedirect(route('courses.show', $course));
    }

    private function createCourseWithLesson(array $courseAttributes = [], array $lessonAttributes = []): array
    {
        $language = Language::create(['name' => 'English']);

        $course = Course::create(array_merge([
            'title' => 'Paid English Course',
            'description' => 'Course description',
            'language_id' => $language->id,
            'price' => 500,
            'is_published' => true,
        ], $courseAttributes));

        $lesson = Lesson::create(array_merge([
            'course_id' => $course->id,
            'title' => 'First lesson',
            'description' => 'Lesson description',
            'content' => 'Lesson content',
            'position' => 1,
        ], $lessonAttributes));

        return [$course, $lesson];
    }
}
