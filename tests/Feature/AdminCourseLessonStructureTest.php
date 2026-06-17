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

    public function test_admin_course_create_form_submits_price_description_and_publish_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $html = $this
            ->actingAs($admin)
            ->get(route('admin.course.create'))
            ->assertOk()
            ->getContent();

        $formStart = strpos($html, '<form');
        $formEnd = strpos($html, '</form>', $formStart);

        $this->assertNotFalse($formStart);
        $this->assertNotFalse($formEnd);
        $this->assertFieldInsideForm($html, 'description', $formStart, $formEnd);
        $this->assertFieldInsideForm($html, 'price', $formStart, $formEnd);
        $this->assertFieldInsideForm($html, 'is_published', $formStart, $formEnd);
    }

    public function test_admin_course_edit_form_submits_price_description_and_publish_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->createCourse(['price' => 900, 'is_published' => true]);

        $html = $this
            ->actingAs($admin)
            ->get(route('admin.course.edit', $course))
            ->assertOk()
            ->getContent();

        $formStart = strpos($html, '<form');
        $formEnd = strpos($html, '</form>', $formStart);

        $this->assertNotFalse($formStart);
        $this->assertNotFalse($formEnd);
        $this->assertFieldInsideForm($html, 'description', $formStart, $formEnd);
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

    public function test_admin_can_create_published_paid_course_with_description(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $language = Language::create(['name' => 'Polish']);

        $this
            ->actingAs($admin)
            ->post(route('admin.course.store'), [
                'title' => 'Polish A1',
                'description' => 'Beginner Polish course',
                'language_id' => $language->id,
                'price' => 1200,
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.course.index'));

        $this->assertDatabaseHas('courses', [
            'title' => 'Polish A1',
            'description' => 'Beginner Polish course',
            'language_id' => $language->id,
            'price' => 1200,
            'is_published' => true,
        ]);
    }

    public function test_admin_can_update_course_and_unpublish_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->createCourse(['price' => 1000, 'is_published' => true]);

        $this
            ->actingAs($admin)
            ->put(route('admin.course.update', $course), [
                'title' => 'Updated English A1',
                'description' => 'Updated course description',
                'language_id' => $course->language_id,
                'price' => 0,
                'is_published' => '0',
            ])
            ->assertRedirect(route('admin.course.index'));

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Updated English A1',
            'description' => 'Updated course description',
            'price' => 0,
            'is_published' => false,
        ]);
    }

    public function test_admin_lesson_edit_form_contains_paid_and_publish_controls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->createCourse();
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Editable lesson',
            'description' => 'Editable description',
            'lesson_type' => 'Reading',
            'position' => 3,
            'price' => 250,
            'is_published' => true,
        ]);

        $html = $this
            ->actingAs($admin)
            ->get(route('admin.course.lesson.edit', $lesson))
            ->assertOk()
            ->getContent();

        $formStart = strpos($html, '<form');
        $formEnd = strpos($html, '</form>', $formStart);

        $this->assertNotFalse($formStart);
        $this->assertNotFalse($formEnd);
        $this->assertFieldInsideForm($html, 'position', $formStart, $formEnd);
        $this->assertFieldInsideForm($html, 'price', $formStart, $formEnd);
        $this->assertFieldInsideForm($html, 'is_published', $formStart, $formEnd);
    }

    public function test_admin_course_show_handles_lesson_homework_files_array(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->createCourse();

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson with homework files',
            'description' => 'Lesson description',
            'lesson_type' => 'Reading',
            'position' => 1,
            'homework_files' => ['homework/file.pdf'],
            'is_published' => true,
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.course.show', $course))
            ->assertOk()
            ->assertSee($lesson->title)
            ->assertSee('Ред. дом.завд.');
    }

    public function test_admin_can_update_lesson_price_position_and_publish_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->createCourse();
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Editable lesson',
            'description' => 'Editable description',
            'lesson_type' => 'Reading',
            'position' => 3,
            'price' => 250,
            'is_published' => true,
        ]);

        $this
            ->actingAs($admin)
            ->put(route('admin.course.lesson.update', $lesson), [
                'lesson_type' => 'Grammar',
                'title' => 'Updated lesson',
                'description' => 'Updated description',
                'position' => 8,
                'price' => 0,
                'is_published' => '0',
            ])
            ->assertRedirect(route('admin.course.show', $course));

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'lesson_type' => 'Grammar',
            'title' => 'Updated lesson',
            'description' => 'Updated description',
            'position' => 8,
            'price' => null,
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
        $fieldPosition = strpos($html, 'name="'.$field.'"', $formStart);

        $this->assertNotFalse($fieldPosition, "Field [{$field}] was not found.");
        $this->assertGreaterThan($formStart, $fieldPosition, "Field [{$field}] is before the form.");
        $this->assertLessThan($formEnd, $fieldPosition, "Field [{$field}] is outside the form.");
    }
}
