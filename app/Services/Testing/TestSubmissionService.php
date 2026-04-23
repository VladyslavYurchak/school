<?php

namespace App\Services\Testing;

use App\Models\Testing\Answer;
use App\Models\Testing\Attempt;
use App\Models\Testing\Session;
use Illuminate\Support\Facades\DB;

class TestSubmissionService
{
    public function __construct(
        protected ScoreCalculatorService $scoreCalculator
    ) {
    }

    public function submitSessionAnswers(Session $session, array $answers): Session
    {
        $session->loadMissing([
            'attempts.test.sections.questions.options',
        ]);

        DB::transaction(function () use ($session, $answers) {
            foreach ($session->attempts as $attempt) {
                $this->submitSingleAttemptAnswers($attempt, $answers);

                $attempt->update([
                    'status' => 'completed',
                    'finished_at' => now(),
                ]);

                $this->scoreCalculator->recalculateAttempt(
                    $attempt->fresh(['test', 'answers'])
                );
            }

            $session->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);

            $this->scoreCalculator->recalculateSession(
                $session->fresh(['attempts.test'])
            );
        });

        return $session->fresh([
            'attempts.test',
            'attempts.answers.question',
            'resultRange',
        ]);
    }

    public function submitSingleAttemptAnswers(Attempt $attempt, array $answers): Attempt
    {
        $attempt->loadMissing([
            'test.sections.questions.options',
        ]);

        $questions = $attempt->test->sections
            ->flatMap(fn ($section) => $section->questions)
            ->keyBy('id');

        DB::transaction(function () use ($attempt, $answers, $questions) {
            foreach ($answers as $questionId => $answerValue) {
                $question = $questions->get((int) $questionId);

                if (! $question) {
                    continue;
                }

                $scored = $this->scoreCalculator->scoreQuestion($question, $answerValue);

                Answer::updateOrCreate(
                    [
                        'attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                    ],
                    [
                        'selected_option_id' => $scored['selected_option_id'],
                        'answer_text' => $scored['answer_text'],
                        'is_correct' => $scored['is_correct'],
                        'awarded_points' => $scored['awarded_points'],
                    ]
                );
            }
        });

        return $attempt->fresh(['answers', 'test']);
    }
}
