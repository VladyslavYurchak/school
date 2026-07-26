<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserVocabularyProgress;
use App\Models\VocabularyItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentVocabularyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_and_non_student_is_forbidden(): void
    {
        $this->get(route('student.vocabulary.learn'))
            ->assertRedirect(route('login'));

        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)
            ->get(route('student.vocabulary.learn'))
            ->assertForbidden();
    }

    public function test_student_sees_words_from_every_kind_of_available_lesson_only_once(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $language = Language::create(['name' => 'English']);

        [$freeCourse, $freeCourseLesson] = $this->courseWithLesson($language, 'Free course', 0);
        $freeCourseWord = $this->attachWord($freeCourseLesson, 'welcome', 'ласкаво просимо');

        [$paidCourse, $freeLesson] = $this->courseWithLesson($language, 'Paid course', 500, 0);
        $freeLessonWord = $this->attachWord($freeLesson, 'gift', 'подарунок');

        $paidCourseLesson = $this->lesson($paidCourse, 'Course lesson', null, 2);
        $paidCourseWord = $this->attachWord($paidCourseLesson, 'journey', 'подорож');
        $student->courses()->attach($paidCourse->id, ['status' => 'paid', 'paid_amount' => 500]);

        [$separateCourse, $separateLesson] = $this->courseWithLesson($language, 'Separate lessons', 600, 200);
        $separateWord = $this->attachWord($separateLesson, 'choice', 'вибір');
        $student->lessons()->attach($separateLesson->id, ['status' => 'paid', 'paid_amount' => 200]);

        $lockedLesson = $this->lesson($separateCourse, 'Locked sibling', 200, 2);
        $lockedWord = $this->attachWord($lockedLesson, 'locked', 'заблокований');

        $freeCourseLesson->vocabularyItems()->attach($paidCourseWord->id, ['position' => 2]);

        $response = $this->actingAs($student)
            ->get(route('student.vocabulary.learn'))
            ->assertOk()
            ->assertSee($freeCourseWord->term)
            ->assertSee($freeLessonWord->term)
            ->assertSee($paidCourseWord->term)
            ->assertSee($separateWord->term)
            ->assertDontSee($lockedWord->term);

        $this->assertSame(
            1,
            substr_count($response->getContent(), 'class="vocabulary-term">'.$paidCourseWord->term.'</div>')
        );
    }

    public function test_unpublished_and_refunded_content_does_not_add_words(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $language = Language::create(['name' => 'English']);

        [$refundedCourse, $refundedLesson] = $this->courseWithLesson($language, 'Refunded', 500);
        $refundedWord = $this->attachWord($refundedLesson, 'refund', 'повернення');
        $student->courses()->attach($refundedCourse->id, ['status' => 'refunded', 'paid_amount' => 500]);

        [$draftCourse, $draftLesson] = $this->courseWithLesson($language, 'Draft', 0);
        $draftCourse->update(['is_published' => false]);
        $draftWord = $this->attachWord($draftLesson, 'draft', 'чернетка');

        [$course, $hiddenLesson] = $this->courseWithLesson($language, 'Visible course', 0);
        $hiddenLesson->update(['is_published' => false]);
        $hiddenWord = $this->attachWord($hiddenLesson, 'secret-zebra-word', 'прихований');

        $this->actingAs($student)
            ->get(route('student.vocabulary.learn'))
            ->assertOk()
            ->assertDontSee($refundedWord->term)
            ->assertDontSee($draftWord->term)
            ->assertDontSee($hiddenWord->term);
    }

    public function test_student_can_mark_word_as_learning_or_known(): void
    {
        [$student, $word] = $this->studentWithAccessibleWord();

        $this->actingAs($student)
            ->post(route('student.vocabulary.progress', $word), ['status' => 'learning'])
            ->assertRedirect(route('student.vocabulary.learn'));

        $this->assertDatabaseHas('user_vocabulary_progress', [
            'user_id' => $student->id,
            'vocabulary_item_id' => $word->id,
            'status' => UserVocabularyProgress::STATUS_LEARNING,
        ]);

        $this->actingAs($student)
            ->post(route('student.vocabulary.progress', $word), ['status' => 'known'])
            ->assertRedirect(route('student.vocabulary.learn'));

        $progress = UserVocabularyProgress::firstOrFail();

        $this->assertSame(UserVocabularyProgress::STATUS_KNOWN, $progress->status);
        $this->assertNotNull($progress->learned_at);
        $this->assertNotNull($progress->next_review_at);

        $this->actingAs($student)
            ->get(route('student.vocabulary.learn'))
            ->assertDontSee($word->term);
    }

    public function test_student_cannot_change_progress_for_inaccessible_word(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $language = Language::create(['name' => 'English']);
        [, $lesson] = $this->courseWithLesson($language, 'Locked course', 500, 200);
        $word = $this->attachWord($lesson, 'private', 'приватний');

        $this->actingAs($student)
            ->post(route('student.vocabulary.progress', $word), ['status' => 'known'])
            ->assertNotFound();

        $this->assertDatabaseCount('user_vocabulary_progress', 0);
    }

    public function test_known_word_appears_in_review_with_accessible_translation_options(): void
    {
        [$student, $word, $distractor] = $this->studentWithTwoAccessibleWords();
        $this->markKnown($student, $word);

        $response = $this->actingAs($student)
            ->get(route('student.vocabulary.review', ['restart' => 1]))
            ->assertOk()
            ->assertSee($word->term)
            ->assertSee($word->translation)
            ->assertSee($distractor->translation);

        $this->assertSame($word->id, $response->viewData('question')->id);
        $this->assertCount(2, $response->viewData('options'));
    }

    public function test_correct_review_updates_progress_and_moves_to_next_interval(): void
    {
        [$student, $word] = $this->studentWithTwoAccessibleWords();
        $progress = $this->markKnown($student, $word);

        $this->actingAs($student)
            ->get(route('student.vocabulary.review', ['restart' => 1]))
            ->assertOk();

        $this->actingAs($student)
            ->post(route('student.vocabulary.review.submit', $word), [
                'selected_id' => $word->id,
            ])
            ->assertRedirect(route('student.vocabulary.review'))
            ->assertSessionHas('vocabulary_review_result.correct', true);

        $progress->refresh();

        $this->assertSame(1, $progress->correct_answers);
        $this->assertSame(0, $progress->incorrect_answers);
        $this->assertSame(1, $progress->correct_streak);
        $this->assertTrue($progress->next_review_at->isFuture());
    }

    public function test_wrong_review_keeps_word_known_and_records_the_error(): void
    {
        [$student, $word, $distractor] = $this->studentWithTwoAccessibleWords();
        $progress = $this->markKnown($student, $word);
        $progress->update(['correct_streak' => 3]);

        $this->actingAs($student)
            ->get(route('student.vocabulary.review', ['restart' => 1]))
            ->assertOk();

        $this->actingAs($student)
            ->post(route('student.vocabulary.review.submit', $word), [
                'selected_id' => $distractor->id,
            ])
            ->assertRedirect(route('student.vocabulary.review'))
            ->assertSessionHas('vocabulary_review_result.correct', false);

        $progress->refresh();

        $this->assertSame(UserVocabularyProgress::STATUS_KNOWN, $progress->status);
        $this->assertSame(1, $progress->incorrect_answers);
        $this->assertSame(0, $progress->correct_streak);
        $this->assertNotNull($progress->last_reviewed_at);
    }

    public function test_review_rejects_an_option_that_was_not_presented(): void
    {
        [$student, $word] = $this->studentWithTwoAccessibleWords();
        $this->markKnown($student, $word);

        $language = Language::firstOrFail();
        [, $lockedLesson] = $this->courseWithLesson($language, 'Locked course', 900, 300);
        $lockedWord = $this->attachWord($lockedLesson, 'forged', 'підроблений');

        $this->actingAs($student)
            ->get(route('student.vocabulary.review', ['restart' => 1]))
            ->assertOk();

        $this->actingAs($student)
            ->from(route('student.vocabulary.review'))
            ->post(route('student.vocabulary.review.submit', $word), [
                'selected_id' => $lockedWord->id,
            ])
            ->assertRedirect(route('student.vocabulary.review'))
            ->assertSessionHasErrors('selected_id');

        $this->assertSame(0, UserVocabularyProgress::firstOrFail()->correct_answers);
    }

    public function test_review_finishes_after_each_known_word_is_answered_once(): void
    {
        [$student, $first, $second] = $this->studentWithTwoAccessibleWords();
        $this->markKnown($student, $first);
        $this->markKnown($student, $second);

        $response = $this->actingAs($student)
            ->get(route('student.vocabulary.review', ['restart' => 1]));
        $firstQuestion = $response->viewData('question');

        $this->actingAs($student)
            ->post(route('student.vocabulary.review.submit', $firstQuestion), [
                'selected_id' => $firstQuestion->id,
            ]);

        $response = $this->actingAs($student)
            ->get(route('student.vocabulary.review'));
        $secondQuestion = $response->viewData('question');

        $this->assertNotSame($firstQuestion->id, $secondQuestion->id);

        $this->actingAs($student)
            ->post(route('student.vocabulary.review.submit', $secondQuestion), [
                'selected_id' => $secondQuestion->id,
            ]);

        $this->actingAs($student)
            ->get(route('student.vocabulary.review'))
            ->assertOk()
            ->assertSee('Повторення завершено');
    }

    public function test_student_navigation_contains_vocabulary_link(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('courses.index'))
            ->assertOk()
            ->assertSee(route('student.vocabulary.learn'), false)
            ->assertSee('Мій словник');
    }

    public function test_course_filter_shows_words_from_selected_accessible_course_only(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $language = Language::create(['name' => 'English']);
        [$firstCourse, $firstLesson] = $this->courseWithLesson($language, 'First free course', 0);
        [, $secondLesson] = $this->courseWithLesson($language, 'Second free course', 0);
        $firstWord = $this->attachWord($firstLesson, 'first-course-only', 'перший');
        $secondWord = $this->attachWord($secondLesson, 'second-course-only', 'другий');

        $this->actingAs($student)
            ->get(route('student.vocabulary.learn', ['course' => $firstCourse->id]))
            ->assertOk()
            ->assertSee($firstWord->term)
            ->assertDontSee($secondWord->term);
    }

    private function studentWithAccessibleWord(): array
    {
        $student = User::factory()->create(['role' => 'student']);
        $language = Language::create(['name' => 'English']);
        [, $lesson] = $this->courseWithLesson($language, 'Free course', 0);

        return [$student, $this->attachWord($lesson, 'bright', 'яскравий')];
    }

    private function studentWithTwoAccessibleWords(): array
    {
        [$student, $first] = $this->studentWithAccessibleWord();
        $lesson = $first->lessons()->firstOrFail();
        $second = $this->attachWord($lesson, 'calm', 'спокійний');

        return [$student, $first, $second];
    }

    private function markKnown(User $user, VocabularyItem $word): UserVocabularyProgress
    {
        $progress = UserVocabularyProgress::create([
            'user_id' => $user->id,
            'vocabulary_item_id' => $word->id,
        ]);
        $progress->markKnown();

        return $progress->fresh();
    }

    private function courseWithLesson(
        Language $language,
        string $title,
        float $coursePrice,
        ?float $lessonPrice = null
    ): array {
        $course = Course::create([
            'title' => $title,
            'description' => $title,
            'language_id' => $language->id,
            'price' => $coursePrice,
            'is_published' => true,
        ]);

        return [$course, $this->lesson($course, $title.' lesson', $lessonPrice)];
    }

    private function lesson(Course $course, string $title, ?float $price, int $position = 1): Lesson
    {
        return Lesson::create([
            'course_id' => $course->id,
            'title' => $title,
            'description' => $title,
            'content' => $title,
            'position' => $position,
            'price' => $price,
            'is_published' => true,
        ]);
    }

    private function attachWord(Lesson $lesson, string $term, string $translation): VocabularyItem
    {
        $word = VocabularyItem::create([
            'language_id' => $lesson->course->language_id,
            'term' => $term,
            'translation' => $translation,
            'transcription' => '/'.$term.'/',
            'part_of_speech' => 'word',
            'example' => ucfirst($term).' example.',
        ]);

        $lesson->vocabularyItems()->attach($word->id, [
            'is_required' => true,
            'position' => $lesson->vocabularyItems()->count() + 1,
        ]);

        return $word;
    }
}
