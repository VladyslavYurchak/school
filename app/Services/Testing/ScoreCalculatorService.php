<?php

namespace App\Services\Testing;

use App\Models\Testing\Attempt;
use App\Models\Testing\Option;
use App\Models\Testing\Question;
use App\Models\Testing\Session;
use App\Models\Testing\Test;
use Illuminate\Support\Facades\DB;
use App\Models\Testing\ResultRange;

class ScoreCalculatorService
{
    public function scoreQuestion(Question $question, mixed $answer): array
    {
        return match ($question->type) {
            'single_choice' => $this->scoreSingleChoice($question, $answer),
            'true_false' => $this->scoreSingleChoice($question, $answer),
            'multiple_choice' => $this->scoreMultipleChoice($question, $answer),
            'short_text' => $this->scoreTextQuestion($question, $answer),
            'long_text' => $this->scoreTextQuestion($question, $answer),
            default => [
                'awarded_points' => 0,
                'is_correct' => false,
                'selected_option_id' => null,
                'answer_text' => is_scalar($answer) ? (string) $answer : null,
            ],
        };
    }

    public function recalculateAttempt(Attempt $attempt): Attempt
    {
        $attempt->loadMissing([
            'test.questions.options',
            'answers',
        ]);

        $rawScore = (float) $attempt->answers->sum(function ($answer) {
            return (float) ($answer->awarded_points ?? 0);
        });

        $maxScore = $this->calculateTestMaxScore($attempt->test);

        $percentScore = $this->calculatePercentScore(
            rawScore: $rawScore,
            maxScore: $maxScore
        );

        $attempt->update([
            'raw_score' => $rawScore,
            'max_score' => $maxScore,
            'weighted_score' => $percentScore,
        ]);

        return $attempt->fresh(['test', 'answers']);
    }

    public function recalculateSession(Session $session): Session
    {
        $session->loadMissing([
            'attempts.test',
            'attempts.answers.question',
        ]);

        $totalRawScore = (float) $session->attempts->sum(fn ($attempt) => (float) $attempt->raw_score);
        $totalMaxScore = (float) $session->attempts->sum(fn ($attempt) => (float) $attempt->max_score);

        $totalPercentScore = $totalMaxScore > 0
            ? round(($totalRawScore / $totalMaxScore) * 100, 2)
            : 0;

        $detector = app(\App\Services\Testing\LevelDetectorService::class);
        $detection = $detector->detectFromSession($session);

        $detectedLevel = $detection['detected_level'];

        $session->update([
            'total_raw_score' => $totalRawScore,
            'total_weighted_score' => $totalPercentScore,
            'max_weighted_score' => 100,
            'detected_level' => $detectedLevel,
            'result_range_id' => null,
        ]);

        return $session->fresh(['attempts.test', 'resultRange']);
    }

    protected function calculatePercentScore(float $rawScore, float $maxScore): float
    {
        if ($maxScore <= 0) {
            return 0;
        }

        return round(($rawScore / $maxScore) * 100, 2);
    }

    public function calculateTestMaxScore(Test $test): float
    {
        $test->loadMissing('questions.options');

        return (float) $test->questions
            ->where('is_active', true)
            ->sum(function (Question $question) {
                return $this->calculateQuestionMaxScore($question);
            });
    }

    public function calculateQuestionMaxScore(Question $question): float
    {
        $question->loadMissing('options');

        return match ($question->type) {
            'single_choice', 'true_false' => $this->calculateSingleChoiceQuestionMaxScore($question),
            'multiple_choice' => $this->calculateMultipleChoiceQuestionMaxScore($question),
            'short_text', 'long_text' => max(0, (float) $question->default_correct_points),
            default => 0,
        };
    }

    protected function scoreSingleChoice(Question $question, mixed $answer): array
    {
        if (blank($answer)) {
            return [
                'awarded_points' => 0,
                'is_correct' => false,
                'selected_option_id' => null,
                'answer_text' => null,
            ];
        }

        $option = $question->options->firstWhere('id', (int) $answer);

        if (! $option) {
            return [
                'awarded_points' => 0,
                'is_correct' => false,
                'selected_option_id' => null,
                'answer_text' => null,
            ];
        }

        $awardedPoints = $this->resolveOptionPoints($question, $option);

        return [
            'awarded_points' => $awardedPoints,
            'is_correct' => $option->is_correct,
            'selected_option_id' => $option->id,
            'answer_text' => null,
        ];
    }

    protected function scoreMultipleChoice(Question $question, mixed $answer): array
    {
        $selectedIds = collect(is_array($answer) ? $answer : [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($selectedIds->isEmpty()) {
            return [
                'awarded_points' => 0,
                'is_correct' => false,
                'selected_option_id' => null,
                'answer_text' => null,
            ];
        }

        $selectedOptions = $question->options->whereIn('id', $selectedIds);
        $awardedPoints = (float) $selectedOptions->sum(fn (Option $option) => $this->resolveOptionPoints($question, $option));

        $correctOptionIds = $question->options
            ->where('is_correct', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values();

        $isCorrect = $selectedIds->sort()->values()->all() === $correctOptionIds->all();

        return [
            'awarded_points' => $awardedPoints,
            'is_correct' => $isCorrect,
            'selected_option_id' => null,
            'answer_text' => json_encode($selectedIds->all(), JSON_UNESCAPED_UNICODE),
        ];
    }

    protected function scoreTextQuestion(Question $question, mixed $answer): array
    {
        return [
            'awarded_points' => 0,
            'is_correct' => null,
            'selected_option_id' => null,
            'answer_text' => is_scalar($answer) ? trim((string) $answer) : null,
        ];
    }

    protected function resolveOptionPoints(Question $question, Option $option): float
    {
        if (! is_null($option->points)) {
            return (float) $option->points;
        }

        return $option->is_correct
            ? (float) $question->default_correct_points
            : (float) $question->default_incorrect_points;
    }

    protected function calculateSingleChoiceQuestionMaxScore(Question $question): float
    {
        $question->loadMissing('options');

        $maxFromOptions = $question->options->max(function (Option $option) use ($question) {
            return $this->resolveOptionPoints($question, $option);
        });

        return max(0, (float) ($maxFromOptions ?? 0));
    }

    protected function calculateMultipleChoiceQuestionMaxScore(Question $question): float
    {
        $question->loadMissing('options');

        $positivePointsSum = (float) $question->options
            ->sum(function (Option $option) use ($question) {
                $points = $this->resolveOptionPoints($question, $option);

                return $points > 0 ? $points : 0;
            });

        return max(0, $positivePointsSum);
    }



    protected function resolveSessionResultRange(Session $session, float $totalWeightedScore): ?ResultRange
    {
        return ResultRange::query()
            ->where(function ($q) use ($session) {
                $q->whereNull('test_id')
                    ->orWhereIn('test_id', $session->attempts->pluck('test_id'));
            })
            ->where('min_score', '<=', $totalWeightedScore)
            ->where('max_score', '>=', $totalWeightedScore)
            ->orderBy('min_score')
            ->first();
    }
}
