<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\LessonExercise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminLessonExerciseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_open_exercise_builder_from_course_and_lesson_pages(): void
    {
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->get(route('admin.course.show', $lesson->course))
            ->assertOk()
            ->assertSee(route('admin.course.lesson.exercises.index', $lesson), false)
            ->assertSee('Додати вправу');

        $this->actingAs($this->admin)
            ->get(route('admin.course.lesson.exercises.index', $lesson))
            ->assertOk()
            ->assertSee('Інтерактивні вправи')
            ->assertSee(route('admin.course.lesson.exercises.create', $lesson), false)
            ->assertSee('Трансформація')
            ->assertSee('Правда / неправда')
            ->assertSee('Диктант');
    }

    public function test_admin_can_create_matching_exercise_with_ordered_pairs(): void
    {
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->post(route('admin.course.lesson.exercises.store', $lesson), [
                'type' => 'matching',
                'title' => 'Match the words',
                'description' => 'Choose the correct translation.',
                'is_active' => 1,
                'pairs' => [
                    ['prompt' => ' challenge ', 'answer' => ' виклик '],
                    ['prompt' => 'goal', 'answer' => 'ціль'],
                ],
            ])
            ->assertRedirect(route('admin.course.lesson.exercises.index', $lesson));

        $exercise = LessonExercise::firstOrFail();

        $this->assertSame('matching', $exercise->type);
        $this->assertTrue($exercise->is_active);
        $this->assertSame(
            [
                ['challenge', 'виклик', 1],
                ['goal', 'ціль', 2],
            ],
            $exercise->items->map(fn ($item) => [$item->prompt, $item->answer, $item->position])->all()
        );
    }

    public function test_admin_can_create_typing_fill_blank_exercise_with_repeated_answers(): void
    {
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->get(route('admin.course.lesson.exercises.create', [
                'lesson' => $lesson,
                'type' => 'fill_blank',
            ]))
            ->assertOk()
            ->assertSee('Заповнити пропущені слова')
            ->assertSee('Введення відповіді')
            ->assertSee('Вибір зі списку');

        $this->actingAs($this->admin)
            ->post(route('admin.course.lesson.exercises.store', $lesson), [
                'type' => 'fill_blank',
                'title' => 'Complete with is',
                'description' => 'Type the missing word.',
                'answer_mode' => 'typing',
                'is_active' => 1,
                'pairs' => [
                    ['prompt' => 'She ___ happy.', 'answer' => 'is'],
                    ['prompt' => 'It ___ cold.', 'answer' => 'is'],
                ],
            ])
            ->assertRedirect(route('admin.course.lesson.exercises.index', $lesson));

        $exercise = LessonExercise::firstOrFail();

        $this->assertSame('fill_blank', $exercise->type);
        $this->assertSame(['answer_mode' => 'typing'], $exercise->settings);
        $this->assertSame(['is', 'is'], $exercise->items()->pluck('answer')->all());
    }

    public function test_fill_blank_requires_one_placeholder_and_valid_answer_mode(): void
    {
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->from(route('admin.course.lesson.exercises.create', [
                'lesson' => $lesson,
                'type' => 'fill_blank',
            ]))
            ->post(route('admin.course.lesson.exercises.store', $lesson), [
                'type' => 'fill_blank',
                'title' => 'Invalid blanks',
                'answer_mode' => 'unsupported',
                'is_active' => 1,
                'pairs' => [
                    ['prompt' => 'No blank here.', 'answer' => 'is'],
                    ['prompt' => 'Too ___ many ___ blanks.', 'answer' => 'are'],
                ],
            ])
            ->assertSessionHasErrors([
                'answer_mode',
                'pairs.0.prompt',
                'pairs.1.prompt',
            ]);

        $this->assertDatabaseCount('lesson_exercises', 0);
    }

    public function test_choice_fill_blank_requires_unique_options(): void
    {
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->post(route('admin.course.lesson.exercises.store', $lesson), [
                'type' => 'fill_blank',
                'title' => 'Choose the answer',
                'answer_mode' => 'choice',
                'is_active' => 1,
                'pairs' => [
                    ['prompt' => 'She ___ happy.', 'answer' => 'is'],
                    ['prompt' => 'It ___ cold.', 'answer' => 'IS'],
                ],
            ])
            ->assertSessionHasErrors([
                'pairs.0.answer',
                'pairs.1.answer',
            ]);

        $this->assertDatabaseCount('lesson_exercises', 0);
    }

    public function test_admin_can_create_word_order_exercise_with_one_sentence_and_optional_hint(): void
    {
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->get(route('admin.course.lesson.exercises.create', [
                'lesson' => $lesson,
                'type' => 'word_order',
            ]))
            ->assertOk()
            ->assertSee('Скласти речення зі слів')
            ->assertSee('Підказка або переклад');

        $this->actingAs($this->admin)
            ->post(route('admin.course.lesson.exercises.store', $lesson), [
                'type' => 'word_order',
                'title' => 'Build a sentence',
                'description' => 'Put the words in order.',
                'is_active' => 1,
                'pairs' => [
                    ['prompt' => '', 'answer' => 'She goes to school every day.'],
                ],
            ])
            ->assertRedirect(route('admin.course.lesson.exercises.index', $lesson));

        $exercise = LessonExercise::firstOrFail();

        $this->assertSame('word_order', $exercise->type);
        $this->assertNull($exercise->settings);
        $this->assertSame('', $exercise->items->first()->prompt);
        $this->assertSame('She goes to school every day.', $exercise->items->first()->answer);
    }

    public function test_word_order_requires_sentence_with_at_least_two_words(): void
    {
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->post(route('admin.course.lesson.exercises.store', $lesson), [
                'type' => 'word_order',
                'title' => 'Invalid sentence',
                'is_active' => 1,
                'pairs' => [
                    ['prompt' => 'Привіт', 'answer' => 'Hello'],
                ],
            ])
            ->assertSessionHasErrors(['pairs.0.answer']);

        $this->assertDatabaseCount('lesson_exercises', 0);
    }

    public function test_admin_can_create_transformation_with_alternative_answers(): void
    {
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->get(route('admin.course.lesson.exercises.create', [
                'lesson' => $lesson,
                'type' => LessonExercise::TYPE_TRANSFORMATION,
            ]))
            ->assertOk()
            ->assertSee('Трансформація речення')
            ->assertSee('Інші допустимі відповіді');

        $this->actingAs($this->admin)
            ->post(route('admin.course.lesson.exercises.store', $lesson), [
                'type' => LessonExercise::TYPE_TRANSFORMATION,
                'title' => 'Make it negative',
                'description' => 'Transform every sentence.',
                'is_active' => 1,
                'pairs' => [[
                    'prompt' => 'She works here. → Зробіть заперечення.',
                    'answer' => 'She does not work here.',
                    'alternatives_text' => "She doesn't work here.\nSHE DOES NOT WORK HERE.",
                ]],
            ])
            ->assertRedirect(route('admin.course.lesson.exercises.index', $lesson));

        $exercise = LessonExercise::firstOrFail();
        $item = $exercise->items->first();

        $this->assertSame(LessonExercise::TYPE_TRANSFORMATION, $exercise->type);
        $this->assertSame([
            'accepted_answers' => [
                'She does not work here.',
                "She doesn't work here.",
            ],
        ], $item->settings);
    }

    public function test_admin_can_create_true_false_with_optional_explanation(): void
    {
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->post(route('admin.course.lesson.exercises.store', $lesson), [
                'type' => LessonExercise::TYPE_TRUE_FALSE,
                'title' => 'Check the text',
                'is_active' => 1,
                'pairs' => [[
                    'prompt' => 'Daniel has two classes.',
                    'answer' => 'true',
                    'explanation' => 'The text mentions two classes.',
                ]],
            ])
            ->assertRedirect(route('admin.course.lesson.exercises.index', $lesson));

        $item = LessonExercise::firstOrFail()->items->first();

        $this->assertSame('true', $item->answer);
        $this->assertSame(
            ['explanation' => 'The text mentions two classes.'],
            $item->settings
        );
    }

    public function test_true_false_rejects_unsupported_answer(): void
    {
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->post(route('admin.course.lesson.exercises.store', $lesson), [
                'type' => LessonExercise::TYPE_TRUE_FALSE,
                'title' => 'Invalid true or false',
                'is_active' => 1,
                'pairs' => [[
                    'prompt' => 'This is a statement.',
                    'answer' => 'maybe',
                ]],
            ])
            ->assertSessionHasErrors(['pairs.0.answer']);

        $this->assertDatabaseCount('lesson_exercises', 0);
    }

    public function test_dictation_requires_supported_audio_for_every_item(): void
    {
        Storage::fake('public');
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->post(route('admin.course.lesson.exercises.store', $lesson), [
                'type' => LessonExercise::TYPE_DICTATION,
                'title' => 'Listen and type',
                'is_active' => 1,
                'pairs' => [[
                    'prompt' => '',
                    'answer' => 'Welcome to our school.',
                ]],
            ])
            ->assertSessionHasErrors(['pairs.0.audio']);

        $this->actingAs($this->admin)
            ->post(route('admin.course.lesson.exercises.store', $lesson), [
                'type' => LessonExercise::TYPE_DICTATION,
                'title' => 'Invalid audio',
                'is_active' => 1,
                'pairs' => [[
                    'prompt' => '',
                    'answer' => 'Welcome to our school.',
                    'audio' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
                ]],
            ])
            ->assertSessionHasErrors(['pairs.0.audio']);

        $this->assertDatabaseCount('lesson_exercises', 0);
    }

    public function test_dictation_allows_multiple_items_without_hints(): void
    {
        Storage::fake('public');
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->post(route('admin.course.lesson.exercises.store', $lesson), [
                'type' => LessonExercise::TYPE_DICTATION,
                'title' => 'Two sentences',
                'is_active' => 1,
                'pairs' => [
                    [
                        'prompt' => '',
                        'answer' => 'Good morning.',
                        'audio' => UploadedFile::fake()->create('first.mp3', 100, 'audio/mpeg'),
                    ],
                    [
                        'prompt' => '',
                        'answer' => 'How are you?',
                        'audio' => UploadedFile::fake()->create('second.mp3', 100, 'audio/mpeg'),
                    ],
                ],
            ])
            ->assertRedirect(route('admin.course.lesson.exercises.index', $lesson));

        $this->assertSame(
            ['', ''],
            LessonExercise::firstOrFail()->items()->pluck('prompt')->all()
        );
    }

    public function test_admin_can_create_update_and_delete_dictation_audio_safely(): void
    {
        Storage::fake('public');
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->post(route('admin.course.lesson.exercises.store', $lesson), [
                'type' => LessonExercise::TYPE_DICTATION,
                'title' => 'Listen and type',
                'description' => 'Capital letters do not matter.',
                'is_active' => 1,
                'pairs' => [[
                    'prompt' => 'A short greeting',
                    'answer' => 'Welcome to our school.',
                    'alternatives_text' => 'Welcome to the school.',
                    'audio' => UploadedFile::fake()->create('greeting.mp3', 300, 'audio/mpeg'),
                ]],
            ])
            ->assertRedirect(route('admin.course.lesson.exercises.index', $lesson));

        $exercise = LessonExercise::firstOrFail();
        $oldItem = $exercise->items->first();
        $oldPath = $oldItem->audio_path;

        Storage::disk('public')->assertExists($oldPath);
        $this->assertSame([
            'accepted_answers' => [
                'Welcome to our school.',
                'Welcome to the school.',
            ],
        ], $oldItem->settings);

        $this->actingAs($this->admin)
            ->put(route('admin.course.lesson.exercises.update', [$lesson, $exercise]), [
                'type' => LessonExercise::TYPE_DICTATION,
                'title' => 'Updated dictation',
                'is_active' => 1,
                'pairs' => [[
                    'existing_item_id' => $oldItem->id,
                    'prompt' => 'Updated greeting',
                    'answer' => 'Good morning.',
                    'audio' => UploadedFile::fake()->create('morning.mp3', 350, 'audio/mpeg'),
                ]],
            ])
            ->assertRedirect(route('admin.course.lesson.exercises.index', $lesson));

        $newPath = $exercise->fresh()->items->first()->audio_path;

        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);

        $this->actingAs($this->admin)
            ->delete(route('admin.course.lesson.exercises.destroy', [$lesson, $exercise]))
            ->assertRedirect();

        Storage::disk('public')->assertMissing($newPath);
    }

    public function test_exercise_requires_complete_unique_pairs(): void
    {
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->from(route('admin.course.lesson.exercises.create', $lesson))
            ->post(route('admin.course.lesson.exercises.store', $lesson), [
                'type' => 'matching',
                'title' => 'Invalid exercise',
                'is_active' => 1,
                'pairs' => [
                    ['prompt' => 'Goal', 'answer' => 'ціль'],
                    ['prompt' => 'goal', 'answer' => ''],
                ],
            ])
            ->assertRedirect(route('admin.course.lesson.exercises.create', $lesson))
            ->assertSessionHasErrors(['pairs.0.prompt', 'pairs.1.prompt', 'pairs.1.answer']);

        $this->assertDatabaseCount('lesson_exercises', 0);
    }

    public function test_exercise_requires_at_least_two_non_empty_pairs(): void
    {
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->from(route('admin.course.lesson.exercises.create', $lesson))
            ->post(route('admin.course.lesson.exercises.store', $lesson), [
                'type' => 'matching',
                'title' => 'Too short',
                'is_active' => 1,
                'pairs' => [
                    ['prompt' => 'Goal', 'answer' => 'ціль'],
                    ['prompt' => ' ', 'answer' => ' '],
                ],
            ])
            ->assertRedirect(route('admin.course.lesson.exercises.create', $lesson))
            ->assertSessionHasErrors(['pairs']);

        $this->assertDatabaseCount('lesson_exercises', 0);
    }

    public function test_admin_can_update_exercise_and_replace_its_pairs(): void
    {
        $lesson = $this->createLesson();
        $exercise = $this->createExercise($lesson, 'Old exercise', 1);

        $this->actingAs($this->admin)
            ->put(route('admin.course.lesson.exercises.update', [$lesson, $exercise]), [
                'type' => 'matching',
                'title' => 'Updated exercise',
                'description' => 'Updated instruction',
                'is_active' => 0,
                'pairs' => [
                    ['prompt' => 'one', 'answer' => 'один'],
                    ['prompt' => 'two', 'answer' => 'два'],
                    ['prompt' => 'three', 'answer' => 'три'],
                ],
            ])
            ->assertRedirect(route('admin.course.lesson.exercises.index', $lesson));

        $exercise->refresh();

        $this->assertSame('Updated exercise', $exercise->title);
        $this->assertFalse($exercise->is_active);
        $this->assertSame(['one', 'two', 'three'], $exercise->items()->pluck('prompt')->all());
        $this->assertDatabaseCount('lesson_exercise_items', 3);
    }

    public function test_exercise_cannot_be_managed_through_another_lesson(): void
    {
        $firstLesson = $this->createLesson();
        $secondLesson = $this->createLesson($firstLesson->course);
        $exercise = $this->createExercise($firstLesson);

        $this->actingAs($this->admin)
            ->get(route('admin.course.lesson.exercises.edit', [$secondLesson, $exercise]))
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->patch(route('admin.course.lesson.exercises.toggle', [$secondLesson, $exercise]))
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->delete(route('admin.course.lesson.exercises.destroy', [$secondLesson, $exercise]))
            ->assertNotFound();

        $this->assertDatabaseHas('lesson_exercises', ['id' => $exercise->id]);
    }

    public function test_admin_can_toggle_and_reorder_complete_exercise_list(): void
    {
        $lesson = $this->createLesson();
        $first = $this->createExercise($lesson, 'First', 1);
        $second = $this->createExercise($lesson, 'Second', 2);

        $this->actingAs($this->admin)
            ->patch(route('admin.course.lesson.exercises.toggle', [$lesson, $first]))
            ->assertRedirect();

        $this->assertFalse($first->fresh()->is_active);

        $this->actingAs($this->admin)
            ->postJson(route('admin.course.lesson.exercises.order', $lesson), [
                'exercises' => [$second->id, $first->id],
            ])
            ->assertOk()
            ->assertJson(['saved' => true]);

        $this->assertSame([$second->id, $first->id], $lesson->exercises()->pluck('id')->all());

        $this->actingAs($this->admin)
            ->postJson(route('admin.course.lesson.exercises.order', $lesson), [
                'exercises' => [$first->id],
            ])
            ->assertUnprocessable();
    }

    public function test_deleting_exercise_deletes_pairs_and_normalizes_positions(): void
    {
        $lesson = $this->createLesson();
        $first = $this->createExercise($lesson, 'First', 1);
        $second = $this->createExercise($lesson, 'Second', 2);
        $firstItemIds = $first->items()->pluck('id')->all();

        $this->actingAs($this->admin)
            ->delete(route('admin.course.lesson.exercises.destroy', [$lesson, $first]))
            ->assertRedirect();

        $this->assertDatabaseMissing('lesson_exercises', ['id' => $first->id]);
        foreach ($firstItemIds as $itemId) {
            $this->assertDatabaseMissing('lesson_exercise_items', ['id' => $itemId]);
        }
        $this->assertSame(1, $second->fresh()->position);
    }

    public function test_non_admin_cannot_manage_lesson_exercises(): void
    {
        $lesson = $this->createLesson();
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('admin.course.lesson.exercises.index', $lesson))
            ->assertRedirect(route('index'));

        $this->actingAs($student)
            ->post(route('admin.course.lesson.exercises.store', $lesson), [
                'type' => 'matching',
                'title' => 'Forbidden',
                'is_active' => 1,
                'pairs' => [
                    ['prompt' => 'a', 'answer' => 'b'],
                    ['prompt' => 'c', 'answer' => 'd'],
                ],
            ])
            ->assertRedirect(route('index'));

        $this->assertDatabaseCount('lesson_exercises', 0);
    }

    public function test_public_lesson_renders_only_active_exercises_in_saved_order(): void
    {
        $lesson = $this->createLesson();
        $second = $this->createExercise($lesson, 'Second active exercise', 2);
        $hidden = $this->createExercise($lesson, 'Hidden exercise', 1);
        $hidden->update(['is_active' => false]);
        $first = $this->createExercise($lesson, 'First active exercise', 1);

        $html = $this->get(route('courses.lessons.show', [$lesson->course, $lesson]))
            ->assertOk()
            ->assertSee('First active exercise')
            ->assertSee('Second active exercise')
            ->assertDontSee('Hidden exercise')
            ->assertSee('data-matching-exercise', false)
            ->assertSee('data-matching-prompts', false)
            ->assertSee('data-matching-answers', false)
            ->getContent();

        $this->assertLessThan(
            strpos($html, 'Second active exercise'),
            strpos($html, 'First active exercise')
        );
    }

    public function test_public_lesson_renders_typing_and_choice_fill_blank_controls(): void
    {
        $lesson = $this->createLesson();
        $typing = $this->createFillBlankExercise($lesson, 'Type answers', 'typing', 1);
        $choice = $this->createFillBlankExercise($lesson, 'Choose answers', 'choice', 2);

        $response = $this->get(route('courses.lessons.show', [$lesson->course, $lesson]))
            ->assertOk()
            ->assertSee('Type answers')
            ->assertSee('Choose answers')
            ->assertSee('data-fill-blank-exercise', false)
            ->assertSee('data-fill-blank-check', false)
            ->assertSee('data-fill-blank-reset', false)
            ->assertSee('She ', false)
            ->assertSee(' every day.', false)
            ->assertDontSee('She ___ every day.');

        $html = $response->getContent();

        $this->assertSame(2, substr_count($html, 'data-fill-blank-exercise'));
        $this->assertSame(2, substr_count($html, '<input type="text" class="lesson-fill-blank-control"'));
        $this->assertSame(2, substr_count($html, '<select class="lesson-fill-blank-control"'));
        $this->assertLessThan(strpos($html, 'Choose answers'), strpos($html, 'Type answers'));

        $this->assertDatabaseHas('lesson_exercises', [
            'id' => $typing->id,
            'type' => 'fill_blank',
        ]);
        $this->assertDatabaseHas('lesson_exercises', [
            'id' => $choice->id,
            'type' => 'fill_blank',
        ]);
    }

    public function test_public_lesson_renders_word_order_tokens_and_optional_hint(): void
    {
        $lesson = $this->createLesson();
        $exercise = $lesson->exercises()->create([
            'type' => 'word_order',
            'title' => 'Build the sentence',
            'description' => 'Click the words in the correct order.',
            'position' => 1,
            'is_active' => true,
        ]);
        $exercise->items()->createMany([
            [
                'prompt' => 'Вона щодня ходить до школи.',
                'answer' => 'She goes to school every day.',
                'position' => 1,
            ],
            [
                'prompt' => '',
                'answer' => 'They are studying now.',
                'position' => 2,
            ],
        ]);

        $html = $this->get(route('courses.lessons.show', [$lesson->course, $lesson]))
            ->assertOk()
            ->assertSee('Build the sentence')
            ->assertSee('Вона щодня ходить до школи.')
            ->assertSee('Складіть правильне речення')
            ->assertSee('data-word-order-exercise', false)
            ->assertSee('data-word-order-selected', false)
            ->assertSee('data-word-order-bank', false)
            ->assertSee('data-word-order-check', false)
            ->assertSee('data-word-order-reset', false)
            ->assertSee('She')
            ->assertSee('day.')
            ->getContent();

        $this->assertSame(10, substr_count($html, 'class="lesson-word-order-token"'));
        $this->assertLessThan(
            strpos($html, 'Підсумковий тест') ?: PHP_INT_MAX,
            strpos($html, 'Build the sentence')
        );
    }

    public function test_public_lesson_renders_transformation_true_false_and_dictation_controls(): void
    {
        Storage::fake('public');
        $lesson = $this->createLesson();

        $transformation = $lesson->exercises()->create([
            'type' => LessonExercise::TYPE_TRANSFORMATION,
            'title' => 'Make it negative',
            'description' => 'Transform the sentence.',
            'position' => 1,
            'is_active' => true,
        ]);
        $transformation->items()->create([
            'prompt' => 'She works here.',
            'answer' => 'She does not work here.',
            'settings' => [
                'accepted_answers' => [
                    'She does not work here.',
                    "She doesn't work here.",
                ],
            ],
            'position' => 1,
        ]);

        $trueFalse = $lesson->exercises()->create([
            'type' => LessonExercise::TYPE_TRUE_FALSE,
            'title' => 'Check the statement',
            'position' => 2,
            'is_active' => true,
        ]);
        $trueFalse->items()->create([
            'prompt' => 'Daniel has two classes.',
            'answer' => 'true',
            'settings' => ['explanation' => 'The text says he has two classes.'],
            'position' => 1,
        ]);

        $audioPath = UploadedFile::fake()
            ->create('dictation.mp3', 200, 'audio/mpeg')
            ->store('lesson-exercises/audio', 'public');
        $dictation = $lesson->exercises()->create([
            'type' => LessonExercise::TYPE_DICTATION,
            'title' => 'Listen carefully',
            'position' => 3,
            'is_active' => true,
        ]);
        $dictation->items()->create([
            'prompt' => '',
            'answer' => 'Welcome home.',
            'settings' => ['accepted_answers' => ['Welcome home.']],
            'audio_path' => $audioPath,
            'position' => 1,
        ]);

        $response = $this->get(route('courses.lessons.show', [$lesson->course, $lesson]))
            ->assertOk()
            ->assertSee('data-text-answer-exercise', false)
            ->assertSee('data-text-answer-control', false)
            ->assertSee('data-true-false-exercise', false)
            ->assertSee('data-true-false-option', false)
            ->assertSee('Правда')
            ->assertSee('Неправда')
            ->assertSee(Storage::url($audioPath), false)
            ->assertSee('controls preload="metadata"', false);

        $html = $response->getContent();

        $this->assertSame(2, substr_count($html, 'data-text-answer-exercise'));
        $this->assertSame(2, substr_count($html, 'data-text-answer-control'));
        $this->assertSame(2, substr_count($html, 'data-true-false-option'));
        $this->assertStringContainsString(
            '&quot;She doesn&#039;t work here.&quot;',
            $html
        );
    }

    public function test_public_exercise_escapes_admin_entered_text_and_appears_before_final_test(): void
    {
        $lesson = $this->createLesson();
        $exercise = $this->createExercise($lesson, '<script>alert(1)</script>', 1);
        $exercise->items()->first()->update([
            'prompt' => '<img src=x onerror=alert(1)>',
            'answer' => '<b>answer</b>',
        ]);

        $test = $lesson->tests()->create([
            'question' => 'Final test marker',
            'position' => 1,
            'is_multiple_choice' => false,
        ]);
        $test->options()->createMany([
            ['option_text' => 'A', 'is_correct' => true],
            ['option_text' => 'B', 'is_correct' => false],
        ]);

        $html = $this->get(route('courses.lessons.show', [$lesson->course, $lesson]))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)
            ->assertSee('&lt;b&gt;answer&lt;/b&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->getContent();

        $this->assertLessThan(
            strpos($html, 'Final test marker'),
            strpos($html, '&lt;script&gt;alert(1)&lt;/script&gt;')
        );
    }

    private function createLesson(?Course $course = null): Lesson
    {
        $course ??= Course::create([
            'title' => 'English A1',
            'description' => 'Course description',
            'language_id' => Language::create([
                'name' => 'English ' . fake()->unique()->numberBetween(1, 9999),
            ])->id,
            'price' => 0,
            'is_published' => true,
        ]);

        return Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson ' . fake()->unique()->numberBetween(1, 9999),
            'description' => 'Description',
            'position' => $course->lessons()->count() + 1,
            'price' => 0,
            'is_published' => true,
        ]);
    }

    private function createExercise(
        Lesson $lesson,
        string $title = 'Match the words',
        int $position = 1
    ): LessonExercise {
        $exercise = $lesson->exercises()->create([
            'type' => 'matching',
            'title' => $title,
            'description' => 'Choose matching pairs.',
            'position' => $position,
            'is_active' => true,
        ]);

        $exercise->items()->createMany([
            ['prompt' => $title . ' prompt one', 'answer' => $title . ' answer one', 'position' => 1],
            ['prompt' => $title . ' prompt two', 'answer' => $title . ' answer two', 'position' => 2],
        ]);

        return $exercise;
    }

    private function createFillBlankExercise(
        Lesson $lesson,
        string $title,
        string $answerMode,
        int $position
    ): LessonExercise {
        $exercise = $lesson->exercises()->create([
            'type' => 'fill_blank',
            'title' => $title,
            'description' => 'Complete every sentence.',
            'settings' => ['answer_mode' => $answerMode],
            'position' => $position,
            'is_active' => true,
        ]);

        $exercise->items()->createMany([
            ['prompt' => 'She ___ every day.', 'answer' => 'studies', 'position' => 1],
            ['prompt' => 'They ___ now.', 'answer' => 'are studying', 'position' => 2],
        ]);

        return $exercise;
    }
}
