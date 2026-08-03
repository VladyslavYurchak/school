<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\LessonTest;
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

    public function test_new_lesson_defaults_to_the_next_course_position(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->createCourse();
        Lesson::create([
            'course_id' => $course->id,
            'title' => 'Existing lesson',
            'description' => 'Existing',
            'lesson_type' => 'Reading',
            'position' => 4,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.course.lesson.store', $course), [
                'lesson_type' => 'Grammar',
                'title' => 'Automatically positioned lesson',
                'description' => 'New lesson',
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.course.show', $course));

        $this->assertDatabaseHas('lessons', [
            'course_id' => $course->id,
            'title' => 'Automatically positioned lesson',
            'position' => 5,
        ]);
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

    public function test_lesson_creation_rejects_an_empty_correct_test_answer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->createCourse();

        $this->actingAs($admin)
            ->post(route('admin.course.lesson.store', $course), [
                'lesson_type' => 'Test',
                'title' => 'Invalid quiz lesson',
                'description' => 'Lesson description',
                'tests' => [[
                    'question' => 'Choose an answer',
                    'answers' => ['A', 'B', 'C', ''],
                    'correct_answer' => 3,
                ]],
            ])
            ->assertSessionHasErrors('tests.0.correct_answer');

        $this->assertDatabaseMissing('lessons', [
            'course_id' => $course->id,
            'title' => 'Invalid quiz lesson',
        ]);
    }

    public function test_lesson_creation_marks_a_single_answer_quiz_as_radio_choice(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->createCourse();

        $this->actingAs($admin)
            ->post(route('admin.course.lesson.store', $course), [
                'lesson_type' => 'Test',
                'title' => 'Single answer quiz',
                'description' => 'Lesson description',
                'tests' => [[
                    'question' => 'Choose one answer',
                    'answers' => ['A', 'B', 'C'],
                    'correct_answer' => 1,
                ]],
            ])
            ->assertRedirect(route('admin.course.show', $course));

        $lesson = Lesson::where('title', 'Single answer quiz')->firstOrFail();
        $test = LessonTest::where('lesson_id', $lesson->id)->firstOrFail();

        $this->assertFalse($test->is_multiple_choice);
        $this->assertSame(1, $test->options()->where('is_correct', true)->count());
    }

    public function test_course_price_cannot_be_empty(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $language = Language::create(['name' => 'German']);

        $this->actingAs($admin)
            ->post(route('admin.course.store'), [
                'title' => 'German A1',
                'description' => 'Course description',
                'language_id' => $language->id,
                'price' => '',
                'is_published' => '0',
            ])
            ->assertSessionHasErrors('price');

        $this->assertDatabaseMissing('courses', ['title' => 'German A1']);
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
            ->assertSee(route('admin.course.lesson.edit', $lesson), false)
            ->assertSee(route('admin.course.lesson.blocks.index', $lesson), false)
            ->assertDontSee(route('admin.course.lesson.main.create', $lesson), false)
            ->assertSee('Редагувати')
            ->assertSee('Домашнє');
    }

    public function test_admin_course_and_lesson_pages_use_unified_admin_layout(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->createCourse();
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Unified lesson',
            'description' => 'Unified description',
            'content' => 'Lesson content',
            'lesson_type' => 'Reading',
            'position' => 1,
            'is_published' => true,
        ]);

        foreach ([
            route('admin.course.create'),
            route('admin.course.edit', $course),
            route('admin.course.show', $course),
            route('admin.course.lesson.create', $course),
            route('admin.course.lesson.edit', $lesson),
            route('admin.course.lesson.show', $lesson),
        ] as $url) {
            $response = $this
                ->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertSee('class="admin-page"', false)
                ->assertSee('class="admin-hero"', false)
                ->assertSee('admin-panel', false);

            $this->assertSame(1, substr_count($response->getContent(), '<main class="app-main">'));
        }
    }

    public function test_admin_lesson_show_renders_rich_text_without_visible_html_tags(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->createCourse();
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Rich lesson',
            'description' => 'Rich description',
            'content' => '<p>Record yourself counting from 1 to 20.</p>',
            'homework_text' => '<p>Practice the same numbers at home.</p>',
            'lesson_type' => 'Reading',
            'position' => 2,
            'is_published' => true,
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.course.lesson.show', $lesson))
            ->assertOk()
            ->assertSee('<p>Record yourself counting from 1 to 20.</p>', false)
            ->assertSee('<p>Practice the same numbers at home.</p>', false)
            ->assertDontSee('&lt;p&gt;Record yourself counting from 1 to 20.&lt;/p&gt;', false)
            ->assertDontSee('&lt;p&gt;Practice the same numbers at home.&lt;/p&gt;', false);
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
            'price' => 0,
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

    public function test_lesson_order_rejects_lessons_from_another_course(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->createCourse();
        $otherCourse = $this->createCourse(['title' => 'Other course']);
        $foreignLesson = Lesson::create([
            'course_id' => $otherCourse->id,
            'title' => 'Foreign lesson',
            'description' => 'Must not move',
            'lesson_type' => 'Reading',
            'position' => 9,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.course.lesson.updateOrder', $course), [
                'lessons' => [['id' => $foreignLesson->id, 'position' => 1]],
            ])
            ->assertUnprocessable();

        $this->assertSame(9, $foreignLesson->fresh()->position);
    }

    public function test_lesson_order_requires_every_lesson_from_the_course(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->createCourse();
        $first = Lesson::create([
            'course_id' => $course->id,
            'title' => 'First lesson',
            'description' => 'First',
            'lesson_type' => 'Reading',
            'position' => 1,
        ]);
        $second = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Second lesson',
            'description' => 'Second',
            'lesson_type' => 'Reading',
            'position' => 2,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.course.lesson.updateOrder', $course), [
                'lessons' => [['id' => $second->id, 'position' => 1]],
            ])
            ->assertUnprocessable();

        $this->assertSame(1, $first->fresh()->position);
        $this->assertSame(2, $second->fresh()->position);
    }

    public function test_lesson_order_is_normalized_from_array_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->createCourse();
        $first = Lesson::create([
            'course_id' => $course->id,
            'title' => 'First lesson',
            'description' => 'First',
            'lesson_type' => 'Reading',
            'position' => 1,
        ]);
        $second = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Second lesson',
            'description' => 'Second',
            'lesson_type' => 'Reading',
            'position' => 2,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.course.lesson.updateOrder', $course), [
                'lessons' => [
                    ['id' => $second->id, 'position' => 99],
                    ['id' => $first->id, 'position' => 99],
                ],
            ])
            ->assertOk();

        $this->assertSame(2, $first->fresh()->position);
        $this->assertSame(1, $second->fresh()->position);
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
