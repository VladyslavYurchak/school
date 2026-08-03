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
            ->assertSee('Мінімум 3 відповіді');
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
            ->assertSee('Мінімум 3 відповіді');
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

    public function test_lesson_test_requires_at_least_one_correct_answer(): void
    {
        $lesson = $this->createLesson();

        $this->post(route('admin.course.lesson.test.store', $lesson), [
            'question' => 'Choose the correct answer',
            'options' => [
                'new' => [
                    ['option_text' => 'A'],
                    ['option_text' => 'B'],
                    ['option_text' => 'C'],
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

        $this->deleteJson(route('admin.course.lesson.test.option.destroy', [
            $lesson,
            $test,
            $correctOption,
        ]))
            ->assertStatus(400)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('lesson_test_options', [
            'id' => $correctOption->id,
            'is_correct' => true,
        ]);
    }

    public function test_update_recalculates_multiple_choice_status_and_updates_options(): void
    {
        $lesson = $this->createLesson();
        $test = LessonTest::create([
            'lesson_id' => $lesson->id,
            'question' => 'Choose the correct answer',
            'position' => 1,
            'is_multiple_choice' => false,
        ]);

        $optionA = $test->options()->create(['option_text' => 'A', 'is_correct' => true]);
        $optionB = $test->options()->create(['option_text' => 'B', 'is_correct' => false]);
        $optionC = $test->options()->create(['option_text' => 'C', 'is_correct' => false]);

        $this->patch(route('admin.course.lesson.test.update', [$lesson, $test]), [
            'question' => 'Choose every correct answer',
            'options' => [
                'existing' => [
                    $optionA->id => ['option_text' => 'Updated A', 'is_correct' => '1'],
                    $optionB->id => ['option_text' => 'Updated B', 'is_correct' => '1'],
                    $optionC->id => ['option_text' => 'Updated C'],
                ],
            ],
        ])->assertRedirect(route('admin.course.lesson.test.create', ['lesson' => $lesson->id]));

        $test->refresh();

        $this->assertTrue($test->is_multiple_choice);
        $this->assertDatabaseHas('lesson_test_options', [
            'id' => $optionA->id,
            'option_text' => 'Updated A',
            'is_correct' => true,
        ]);
        $this->assertDatabaseHas('lesson_test_options', [
            'id' => $optionB->id,
            'option_text' => 'Updated B',
            'is_correct' => true,
        ]);
    }

    public function test_deleting_correct_option_recalculates_multiple_choice_status(): void
    {
        $lesson = $this->createLesson();
        $test = LessonTest::create([
            'lesson_id' => $lesson->id,
            'question' => 'Choose every correct answer',
            'position' => 1,
            'is_multiple_choice' => true,
        ]);

        $test->options()->create(['option_text' => 'A', 'is_correct' => true]);
        $correctOptionToDelete = $test->options()->create(['option_text' => 'B', 'is_correct' => true]);
        $test->options()->create(['option_text' => 'C', 'is_correct' => false]);
        $test->options()->create(['option_text' => 'D', 'is_correct' => false]);

        $this->deleteJson(route('admin.course.lesson.test.option.destroy', [
            $lesson,
            $test,
            $correctOptionToDelete,
        ]))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertFalse($test->fresh()->is_multiple_choice);
        $this->assertDatabaseMissing('lesson_test_options', [
            'id' => $correctOptionToDelete->id,
        ]);
    }

    public function test_answer_option_cannot_be_deleted_through_another_test_or_lesson(): void
    {
        $lesson = $this->createLesson();
        $otherLesson = Lesson::create([
            'course_id' => $lesson->course_id,
            'title' => 'Other lesson',
            'description' => 'Other lesson description',
            'position' => 2,
        ]);
        $test = $lesson->tests()->create([
            'question' => 'Original test',
            'position' => 1,
            'is_multiple_choice' => false,
        ]);
        $otherTest = $otherLesson->tests()->create([
            'question' => 'Other test',
            'position' => 1,
            'is_multiple_choice' => false,
        ]);
        $option = $test->options()->create([
            'option_text' => 'Protected answer',
            'is_correct' => false,
        ]);

        $this->deleteJson(route('admin.course.lesson.test.option.destroy', [
            $otherLesson,
            $otherTest,
            $option,
        ]))->assertNotFound();

        $this->assertDatabaseHas('lesson_test_options', ['id' => $option->id]);
    }

    public function test_test_cannot_be_deleted_through_another_lesson(): void
    {
        $lesson = $this->createLesson();
        $otherLesson = Lesson::create([
            'course_id' => $lesson->course_id,
            'title' => 'Other lesson',
            'description' => 'Other lesson description',
            'position' => 2,
        ]);
        $test = $lesson->tests()->create([
            'question' => 'Protected test',
            'position' => 1,
            'is_multiple_choice' => false,
        ]);

        $this->delete(route('admin.course.lesson.test.destroy', [$otherLesson, $test]))
            ->assertNotFound();

        $this->assertDatabaseHas('lesson_tests', ['id' => $test->id]);
    }

    public function test_test_cannot_be_edited_through_another_lesson(): void
    {
        $lesson = $this->createLesson();
        $otherLesson = Lesson::create([
            'course_id' => $lesson->course_id,
            'title' => 'Other lesson',
            'description' => 'Other lesson description',
            'position' => 2,
        ]);
        $test = $lesson->tests()->create([
            'question' => 'Protected test',
            'position' => 1,
            'is_multiple_choice' => false,
        ]);

        $this->get(route('admin.course.lesson.test.edit', [$otherLesson, $test]))
            ->assertNotFound();
    }

    public function test_test_order_rejects_tests_from_another_lesson(): void
    {
        $lesson = $this->createLesson();
        $otherLesson = Lesson::create([
            'course_id' => $lesson->course_id,
            'title' => 'Other lesson',
            'description' => 'Other lesson description',
            'position' => 2,
        ]);
        $foreignTest = $otherLesson->tests()->create([
            'question' => 'Foreign test',
            'position' => 7,
            'is_multiple_choice' => false,
        ]);

        $this->postJson(route('admin.course.lesson.test.updateOrder', $lesson), [
            'order' => [['id' => $foreignTest->id, 'position' => 1]],
        ])->assertUnprocessable();

        $this->assertSame(7, $foreignTest->fresh()->position);
    }

    public function test_test_order_requires_every_test_from_the_lesson(): void
    {
        $lesson = $this->createLesson();
        $first = $lesson->tests()->create([
            'question' => 'First test',
            'position' => 1,
            'is_multiple_choice' => false,
        ]);
        $second = $lesson->tests()->create([
            'question' => 'Second test',
            'position' => 2,
            'is_multiple_choice' => false,
        ]);

        $this->postJson(route('admin.course.lesson.test.updateOrder', $lesson), [
            'order' => [['id' => $second->id, 'position' => 1]],
        ])->assertUnprocessable();

        $this->assertSame(1, $first->fresh()->position);
        $this->assertSame(2, $second->fresh()->position);
    }

    public function test_test_order_is_normalized_from_array_order(): void
    {
        $lesson = $this->createLesson();
        $first = $lesson->tests()->create([
            'question' => 'First test',
            'position' => 1,
            'is_multiple_choice' => false,
        ]);
        $second = $lesson->tests()->create([
            'question' => 'Second test',
            'position' => 2,
            'is_multiple_choice' => false,
        ]);

        $this->postJson(route('admin.course.lesson.test.updateOrder', $lesson), [
            'order' => [
                ['id' => $second->id, 'position' => 99],
                ['id' => $first->id, 'position' => 99],
            ],
        ])->assertOk();

        $this->assertSame(2, $first->fresh()->position);
        $this->assertSame(1, $second->fresh()->position);
    }

    public function test_update_rejects_answer_options_from_another_test(): void
    {
        $lesson = $this->createLesson();
        $test = $lesson->tests()->create([
            'question' => 'Original test',
            'position' => 1,
            'is_multiple_choice' => false,
        ]);
        $ownOptions = $test->options()->createMany([
            ['option_text' => 'A', 'is_correct' => true],
            ['option_text' => 'B', 'is_correct' => false],
            ['option_text' => 'C', 'is_correct' => false],
        ]);
        $otherTest = $lesson->tests()->create([
            'question' => 'Other test',
            'position' => 2,
            'is_multiple_choice' => false,
        ]);
        $foreignOption = $otherTest->options()->create([
            'option_text' => 'Foreign correct answer',
            'is_correct' => true,
        ]);

        $this->patch(route('admin.course.lesson.test.update', [$lesson, $test]), [
            'question' => 'Tampered update',
            'options' => [
                'existing' => [
                    $ownOptions[0]->id => ['option_text' => 'A'],
                    $ownOptions[1]->id => ['option_text' => 'B'],
                    $foreignOption->id => ['option_text' => 'Foreign', 'is_correct' => '1'],
                ],
            ],
        ])->assertSessionHasErrors('options');

        $this->assertSame('Original test', $test->fresh()->question);
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
