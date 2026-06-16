<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCourseLessonStructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_admin_lesson_create_form_submits_position_price_and_publish_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->createCourse();

        $html = $this
            ->actingAs($admin)
            ->get(route('admin.course.lesson.create', $course))
            ->assertOk()
            ->getContent();

        $formStart = strpos($html, '<form');
        $formEnd = strpos($html, '</form>', $formStart);

        $this->assertNotFalse($formStart);
        $this->assertNotFalse($formEnd);
        $this->assertStringContainsString($course->title, $html);
        $this->assertFieldInsideForm($html, 'position', $formStart, $formEnd);
        $this->assertFieldInsideForm($html, 'price', $formStart, $formEnd);
        $this->assertFieldInsideForm($html, 'is_published', $formStart, $formEnd);
    }

    public function test_admin_can_create_paid_unpublished_lesson_with_position(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->createCourse();

        $this
            ->actingAs($admin)
            ->post(route('admin.course.lesson.store', $course), [
                'lesson_type' => 'Reading',
                'title' => 'Draft paid lesson',
                'description' => 'Lesson description',
                'position' => 7,
                'price' => 350,
                'is_published' => '0',
            ])
            ->assertRedirect(route('admin.course.show', $course));

        $this->assertDatabaseHas('lessons', [
            'course_id' => $course->id,
            'title' => 'Draft paid lesson',
            'position' => 7,
            'price' => 350,
            'is_published' => false,
        ]);
    }

    public function test_public_course_pages_hide_unpublished_lessons(): void
    {
        $course = $this->createCourse(['is_published' => true, 'price' => 0]);

        $publishedLesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Published lesson',
            'description' => 'Visible lesson',
            'position' => 1,
            'is_published' => true,
        ]);

        $draftLesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Draft lesson',
            'description' => 'Hidden lesson',
            'position' => 2,
            'is_published' => false,
        ]);

        $this
            ->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee($publishedLesson->title)
            ->assertDontSee($draftLesson->title);

        $this
            ->get(route('courses.lessons.show', [$course, $publishedLesson]))
            ->assertOk();

        $this
            ->get(route('courses.lessons.show', [$course, $draftLesson]))
            ->assertNotFound();
    }

    private function createCourse(array $attributes = []): Course
    {
        $language = Language::create(['name' => 'English']);

        return Course::create(array_merge([
            'title' => 'English A1',
            'description' => 'Course description',
            'language_id' => $language->id,
            'price' => 0,
            'is_published' => true,
        ], $attributes));
    }

    private function assertFieldInsideForm(string $html, string $field, int $formStart, int $formEnd): void
    {
        $fieldPosition = strpos($html, 'name="'.$field.'"');

        $this->assertNotFalse($fieldPosition, "Field [{$field}] was not found.");
        $this->assertGreaterThan($formStart, $fieldPosition, "Field [{$field}] is before the form.");
        $this->assertLessThan($formEnd, $fieldPosition, "Field [{$field}] is outside the form.");
    }
}
