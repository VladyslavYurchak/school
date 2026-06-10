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

    private function createCourseWithLesson(array $courseAttributes = []): array
    {
        $language = Language::create(['name' => 'English']);

        $course = Course::create(array_merge([
            'title' => 'Paid English Course',
            'description' => 'Course description',
            'language_id' => $language->id,
            'price' => 500,
            'is_published' => true,
        ], $courseAttributes));

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'First lesson',
            'description' => 'Lesson description',
            'content' => 'Lesson content',
            'position' => 1,
        ]);

        return [$course, $lesson];
    }
}
