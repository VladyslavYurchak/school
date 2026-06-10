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

class LessonTestAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_lesson_test_requires_at_least_three_answers(): void
    {
        $lesson = $this->createLesson();

        $this->post(route('admin.course.lesson.test.store', $lesson), [
            'question' => 'Choose the correct answer',
            'options' => [
                'new' => [
                    ['option_text' => 'A', 'is_correct' => '1'],
                    ['option_text' => 'B'],
                ],
            ],
        ])->assertSessionHasErrors('options');

        $this->assertDatabaseCount('lesson_tests', 0);
    }

    public function test_lesson_test_create_page_renders(): void
    {
        $lesson = $this->createLesson();

        $this->get(route('admin.course.lesson.test.create', $lesson))
            ->assertOk()
            ->assertViewIs('admin.course.lesson.test.create')
            ->assertSee('Minimum 3 answers');
    }

    public function test_lesson_test_edit_page_renders(): void
    {
        $lesson = $this->createLesson();
        $test = LessonTest::create([
            'lesson_id' => $lesson->id,
            'question' => 'Choose the correct answer',
            'position' => 1,
            'is_multiple_choice' => false,
        ]);

        $test->options()->createMany([
            ['option_text' => 'A', 'is_correct' => true],
            ['option_text' => 'B', 'is_correct' => false],
            ['option_text' => 'C', 'is_correct' => false],
        ]);

        $this->get(route('admin.course.lesson.test.edit', [$lesson, $test]))
            ->assertOk()
            ->assertViewIs('admin.course.lesson.test.edit')
            ->assertSee('Minimum 3 answers');
    }

    public function test_lesson_test_allows_no_more_than_five_answers(): void
    {
        $lesson = $this->createLesson();

        $this->post(route('admin.course.lesson.test.store', $lesson), [
            'question' => 'Choose the correct answer',
            'options' => [
                'new' => [
                    ['option_text' => 'A', 'is_correct' => '1'],
                    ['option_text' => 'B'],
                    ['option_text' => 'C'],
                    ['option_text' => 'D'],
                    ['option_text' => 'E'],
                    ['option_text' => 'F'],
                ],
            ],
        ])->assertSessionHasErrors('options');

        $this->assertDatabaseCount('lesson_tests', 0);
    }

    public function test_single_correct_answer_creates_single_choice_test(): void
    {
        $lesson = $this->createLesson();

        $this->post(route('admin.course.lesson.test.store', $lesson), [
            'question' => 'Choose the correct answer',
            'options' => [
                'new' => [
                    ['option_text' => 'A', 'is_correct' => '1'],
                    ['option_text' => 'B'],
                    ['option_text' => 'C'],
                ],
            ],
        ])->assertRedirect(route('admin.course.lesson.test.create', $lesson));

        $test = LessonTest::with('options')->firstOrFail();

        $this->assertFalse($test->is_multiple_choice);
        $this->assertSame(3, $test->options->count());
        $this->assertSame(1, $test->options->where('is_correct', true)->count());
    }

    public function test_multiple_correct_answers_create_multiple_choice_test(): void
    {
        $lesson = $this->createLesson();

        $this->post(route('admin.course.lesson.test.store', $lesson), [
            'question' => 'Choose every correct answer',
            'options' => [
                'new' => [
                    ['option_text' => 'A', 'is_correct' => '1'],
                    ['option_text' => 'B', 'is_correct' => '1'],
                    ['option_text' => 'C'],
                ],
            ],
        ])->assertRedirect(route('admin.course.lesson.test.create', $lesson));

        $test = LessonTest::with('options')->firstOrFail();

        $this->assertTrue($test->is_multiple_choice);
        $this->assertSame(2, $test->options->where('is_correct', true)->count());
    }

    public function test_cannot_delete_the_only_correct_answer(): void
    {
        $lesson = $this->createLesson();
        $test = LessonTest::create([
            'lesson_id' => $lesson->id,
            'question' => 'Choose the correct answer',
            'position' => 1,
            'is_multiple_choice' => false,
        ]);

        $correctOption = $test->options()->create(['option_text' => 'A', 'is_correct' => true]);
        $test->options()->createMany([
            ['option_text' => 'B', 'is_correct' => false],
            ['option_text' => 'C', 'is_correct' => false],
            ['option_text' => 'D', 'is_correct' => false],
        ]);

        $this->deleteJson(route('admin.course.lesson.test.option.destroy', $correctOption))
            ->assertStatus(400)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('lesson_test_options', [
            'id' => $correctOption->id,
            'is_correct' => true,
        ]);
    }

    private function createLesson(): Lesson
    {
        $language = Language::create(['name' => 'English']);
        $course = Course::create([
            'title' => 'English',
            'description' => 'Course description',
            'language_id' => $language->id,
            'price' => 0,
            'is_published' => true,
        ]);

        return Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson 1',
            'description' => 'Lesson description',
            'position' => 1,
        ]);
    }
}
