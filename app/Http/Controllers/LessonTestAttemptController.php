<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonTestAttempt;
use App\Models\LessonTestAttemptAnswer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LessonTestAttemptController extends Controller
{
    public function store(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user, 403);
        abort_unless($course->is_published || $user->isAdmin(), 404);
        abort_unless($lesson->course_id === $course->id, 404);
        abort_unless($lesson->is_published || $user->isAdmin(), 404);

        if (! $lesson->isAvailableFor($user)) {
            return redirect()
                ->route('courses.show', $course)
                ->with('error', 'Спочатку відкрийте доступ до уроку.');
        }

        $lesson->load('tests.options');

        if ($lesson->tests->isEmpty()) {
            return redirect()
                ->route('courses.lessons.show', [$course, $lesson])
                ->with('error', 'У цьому уроці немає тесту.');
        }

        $answers = $request->input('answers', []);

        DB::transaction(function () use ($user, $lesson, $answers) {
            $score = 0;
            $maxScore = $lesson->tests->count();

            $attempt = LessonTestAttempt::create([
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'score' => 0,
                'max_score' => $maxScore,
                'percent' => 0,
                'passed' => false,
                'finished_at' => now(),
            ]);

            foreach ($lesson->tests as $test) {
                $isCorrect = false;
                $selectedOptionIds = null;
                $answerText = null;

                if ($test->options->count()) {
                    $correctIds = $test->options
                        ->where('is_correct', true)
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->sort()
                        ->values()
                        ->all();

                    $rawAnswer = $answers[$test->id] ?? [];

                    $selectedIds = is_array($rawAnswer)
                        ? $rawAnswer
                        : [$rawAnswer];

                    $selectedIds = collect($selectedIds)
                        ->filter()
                        ->map(fn ($id) => (int) $id)
                        ->sort()
                        ->values()
                        ->all();

                    $selectedOptionIds = $selectedIds;

                    $isCorrect = $selectedIds === $correctIds;
                } else {
                    $answerText = trim((string) ($answers[$test->id] ?? ''));

                    $isCorrect = $this->normalizeText($answerText) === $this->normalizeText((string) $test->correct_answer);
                }

                if ($isCorrect) {
                    $score++;
                }

                LessonTestAttemptAnswer::create([
                    'lesson_test_attempt_id' => $attempt->id,
                    'lesson_test_id' => $test->id,
                    'selected_option_ids' => $selectedOptionIds,
                    'answer_text' => $answerText,
                    'is_correct' => $isCorrect,
                ]);
            }

            $percent = $maxScore > 0
                ? (int) round(($score / $maxScore) * 100)
                : 0;

            $attempt->update([
                'score' => $score,
                'max_score' => $maxScore,
                'percent' => $percent,
                'passed' => $percent >= 70,
            ]);
        });

        return redirect()
            ->route('courses.lessons.show', [$course, $lesson])
            ->with('success', 'Тест завершено. Результат збережено.');
    }

    private function normalizeText(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value)));
    }
}
