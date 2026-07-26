<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\LessonVocabularyItem;
use App\Models\User;
use App\Models\VocabularyItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLessonVocabularyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_open_lesson_vocabulary_pages(): void
    {
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->get(route('admin.course.lesson.vocabulary.index', $lesson))
            ->assertOk()
            ->assertSee('Словник уроку')
            ->assertSee('Знайти у загальному словнику')
            ->assertSee('Нове слово')
            ->assertDontSee('<style>', false);

        $this->actingAs($this->admin)
            ->get(route('admin.course.lesson.vocabulary.create', $lesson))
            ->assertOk()
            ->assertSee('Слово або фраза')
            ->assertSee('Основний переклад')
            ->assertSee('Обов’язкове для вивчення');
    }

    public function test_non_admin_users_cannot_manage_lesson_vocabulary(): void
    {
        $lesson = $this->createLesson();
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        foreach ([$teacher, $student] as $user) {
            $this->actingAs($user)
                ->get(route('admin.course.lesson.vocabulary.index', $lesson))
                ->assertRedirect(route('index'));

            $this->actingAs($user)
                ->post(route('admin.course.lesson.vocabulary.store', $lesson), $this->wordData())
                ->assertRedirect(route('index'));
        }

        $this->assertDatabaseCount('vocabulary_items', 0);
        $this->assertDatabaseCount('lesson_vocabulary_items', 0);
    }

    public function test_vocabulary_word_requires_term_and_translation(): void
    {
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->from(route('admin.course.lesson.vocabulary.create', $lesson))
            ->post(route('admin.course.lesson.vocabulary.store', $lesson), [
                ...$this->wordData(),
                'term' => '   ',
                'translation' => '',
            ])
            ->assertRedirect(route('admin.course.lesson.vocabulary.create', $lesson))
            ->assertSessionHasErrors(['term', 'translation']);

        $this->assertDatabaseCount('vocabulary_items', 0);
        $this->assertDatabaseCount('lesson_vocabulary_items', 0);
    }

    public function test_creating_word_uses_course_language_and_attaches_it_to_lesson(): void
    {
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->post(route('admin.course.lesson.vocabulary.store', $lesson), $this->wordData())
            ->assertRedirect(route('admin.course.lesson.vocabulary.index', $lesson));

        $item = VocabularyItem::firstOrFail();

        $this->assertSame($lesson->course->language_id, $item->language_id);
        $this->assertSame('challenge', $item->term);
        $this->assertSame('виклик', $item->translation);
        $this->assertDatabaseHas('lesson_vocabulary_items', [
            'lesson_id' => $lesson->id,
            'vocabulary_item_id' => $item->id,
            'is_required' => true,
            'position' => 1,
        ]);
    }

    public function test_exact_duplicate_reuses_global_item_without_duplicate_attachment(): void
    {
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->post(route('admin.course.lesson.vocabulary.store', $lesson), $this->wordData());
        $this->actingAs($this->admin)
            ->post(route('admin.course.lesson.vocabulary.store', $lesson), [
                ...$this->wordData(),
                'term' => 'Challenge',
                'translation' => 'ВИКЛИК',
            ]);

        $this->assertDatabaseCount('vocabulary_items', 1);
        $this->assertDatabaseCount('lesson_vocabulary_items', 1);
    }

    public function test_existing_word_can_be_found_and_attached_to_another_lesson(): void
    {
        $firstLesson = $this->createLesson();
        $secondLesson = $this->createLesson($firstLesson->course);
        $item = VocabularyItem::create([
            'language_id' => $firstLesson->course->language_id,
            'term' => 'look after',
            'translation' => 'доглядати',
        ]);
        $firstLesson->vocabularyItems()->attach($item, ['position' => 1]);

        $this->actingAs($this->admin)
            ->get(route('admin.course.lesson.vocabulary.index', [
                'lesson' => $secondLesson,
                'q' => 'доглядати',
            ]))
            ->assertOk()
            ->assertSee('look after')
            ->assertSee('доглядати');

        $this->actingAs($this->admin)
            ->post(route('admin.course.lesson.vocabulary.attach', [$secondLesson, $item]))
            ->assertRedirect();

        $this->assertDatabaseCount('vocabulary_items', 1);
        $this->assertDatabaseHas('lesson_vocabulary_items', [
            'lesson_id' => $secondLesson->id,
            'vocabulary_item_id' => $item->id,
        ]);
    }

    public function test_word_from_another_language_cannot_be_attached(): void
    {
        $lesson = $this->createLesson();
        $otherLanguage = Language::create(['name' => 'Polish']);
        $item = VocabularyItem::create([
            'language_id' => $otherLanguage->id,
            'term' => 'dom',
            'translation' => 'будинок',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.course.lesson.vocabulary.attach', [$lesson, $item]))
            ->assertNotFound();

        $this->assertDatabaseCount('lesson_vocabulary_items', 0);
    }

    public function test_admin_can_update_global_word_and_lesson_specific_fields(): void
    {
        $lesson = $this->createLesson();
        $item = VocabularyItem::create([
            'language_id' => $lesson->course->language_id,
            'term' => 'run',
            'translation' => 'бігти',
        ]);
        $link = LessonVocabularyItem::create([
            'lesson_id' => $lesson->id,
            'vocabulary_item_id' => $item->id,
            'position' => 1,
            'is_required' => false,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.course.lesson.vocabulary.update', [$lesson, $link]), [
                ...$this->wordData(),
                'term' => 'run',
                'translation' => 'бігти; керувати; працювати',
                'note' => 'Focus on the verb meaning.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('vocabulary_items', [
            'id' => $item->id,
            'translation' => 'бігти; керувати; працювати',
        ]);
        $this->assertDatabaseHas('lesson_vocabulary_items', [
            'id' => $link->id,
            'is_required' => true,
            'note' => 'Focus on the verb meaning.',
        ]);
    }

    public function test_detaching_word_keeps_global_item_and_normalizes_positions(): void
    {
        $lesson = $this->createLesson();
        $first = $this->attachWord($lesson, 'first', 1);
        $second = $this->attachWord($lesson, 'second', 2);

        $this->actingAs($this->admin)
            ->delete(route('admin.course.lesson.vocabulary.detach', [$lesson, $first]))
            ->assertRedirect();

        $this->assertDatabaseHas('vocabulary_items', ['id' => $first->vocabulary_item_id]);
        $this->assertDatabaseMissing('lesson_vocabulary_items', ['id' => $first->id]);
        $this->assertSame(1, $second->fresh()->position);
    }

    public function test_link_cannot_be_edited_or_detached_through_another_lesson(): void
    {
        $firstLesson = $this->createLesson();
        $secondLesson = $this->createLesson($firstLesson->course);
        $link = $this->attachWord($firstLesson, 'private', 1);

        $this->actingAs($this->admin)
            ->get(route('admin.course.lesson.vocabulary.edit', [$secondLesson, $link]))
            ->assertNotFound();
        $this->actingAs($this->admin)
            ->delete(route('admin.course.lesson.vocabulary.detach', [$secondLesson, $link]))
            ->assertNotFound();

        $this->assertDatabaseHas('lesson_vocabulary_items', ['id' => $link->id]);
    }

    public function test_admin_can_reorder_words_but_not_links_from_another_lesson(): void
    {
        $lesson = $this->createLesson();
        $otherLesson = $this->createLesson($lesson->course);
        $first = $this->attachWord($lesson, 'first', 1);
        $second = $this->attachWord($lesson, 'second', 2);
        $foreign = $this->attachWord($otherLesson, 'foreign', 1);

        $this->actingAs($this->admin)
            ->postJson(route('admin.course.lesson.vocabulary.order', $lesson), [
                'links' => [$second->id, $first->id],
            ])
            ->assertOk()
            ->assertJson(['saved' => true]);

        $this->assertSame([$second->id, $first->id], $lesson->vocabularyLinks()->pluck('id')->all());

        $this->actingAs($this->admin)
            ->postJson(route('admin.course.lesson.vocabulary.order', $lesson), [
                'links' => [$foreign->id],
            ])
            ->assertUnprocessable();
    }

    public function test_reorder_requires_complete_list_of_current_lesson_words(): void
    {
        $lesson = $this->createLesson();
        $first = $this->attachWord($lesson, 'first', 1);
        $second = $this->attachWord($lesson, 'second', 2);

        $this->actingAs($this->admin)
            ->postJson(route('admin.course.lesson.vocabulary.order', $lesson), [
                'links' => [$second->id],
            ])
            ->assertUnprocessable();

        $this->assertSame([$first->id, $second->id], $lesson->vocabularyLinks()->pluck('id')->all());
    }

    public function test_course_and_lesson_pages_still_open_when_lesson_has_no_vocabulary(): void
    {
        $lesson = $this->createLesson();

        $this->actingAs($this->admin)
            ->get(route('admin.course.show', $lesson->course))
            ->assertOk()
            ->assertSee(route('admin.course.lesson.vocabulary.index', $lesson), false);

        $this->actingAs($this->admin)
            ->get(route('admin.course.lesson.show', $lesson))
            ->assertOk()
            ->assertSee($lesson->title);
    }

    public function test_course_and_lesson_pages_show_vocabulary_entry_points_and_preview(): void
    {
        $lesson = $this->createLesson();
        $link = $this->attachWord($lesson, 'essential', 1);
        $link->update(['is_required' => true]);

        $this->actingAs($this->admin)
            ->get(route('admin.course.show', $lesson->course))
            ->assertOk()
            ->assertSee('Словник (1)')
            ->assertSee(route('admin.course.lesson.vocabulary.index', $lesson), false);

        $this->actingAs($this->admin)
            ->get(route('admin.course.lesson.show', $lesson))
            ->assertOk()
            ->assertSee('Словник уроку')
            ->assertSee('essential')
            ->assertSee('translation essential')
            ->assertSee('Обов’язкове');
    }

    private function createLesson(?Course $course = null): Lesson
    {
        $course ??= Course::create([
            'title' => 'English A1',
            'description' => 'Course description',
            'language_id' => Language::create(['name' => 'English ' . fake()->unique()->numberBetween(1, 9999)])->id,
            'price' => 0,
            'is_published' => true,
        ]);

        return Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson ' . fake()->unique()->numberBetween(1, 9999),
            'description' => 'Description',
            'position' => $course->lessons()->count() + 1,
            'is_published' => true,
        ]);
    }

    private function attachWord(Lesson $lesson, string $term, int $position): LessonVocabularyItem
    {
        $item = VocabularyItem::create([
            'language_id' => $lesson->course->language_id,
            'term' => $term,
            'translation' => 'translation ' . $term,
        ]);

        return LessonVocabularyItem::create([
            'lesson_id' => $lesson->id,
            'vocabulary_item_id' => $item->id,
            'position' => $position,
            'is_required' => false,
        ]);
    }

    private function wordData(): array
    {
        return [
            'term' => 'challenge',
            'translation' => 'виклик',
            'transcription' => '/ˈtʃælɪndʒ/',
            'part_of_speech' => 'noun',
            'explanation' => 'Something difficult that tests your ability.',
            'example' => 'Learning English is a challenge.',
            'example_translation' => 'Вивчення англійської — це виклик.',
            'is_required' => 1,
            'note' => 'Key word for the lesson.',
        ];
    }
}
