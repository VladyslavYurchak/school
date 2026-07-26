<?php

namespace App\Services\Testing;

use App\Models\Testing\Attempt;
use App\Models\Testing\Session;

class LevelDetectorService
{
    public function detectFromSession(Session $session): array
    {
        $session->loadMissing([
            'attempts.test.sections.questions',
            'attempts.answers.question',
        ]);

        $questions = $session->attempts
            ->flatMap(fn (Attempt $attempt) => $attempt->test->sections)
            ->where('is_active', true)
            ->flatMap(fn ($section) => $section->questions)
            ->where('is_active', true)
            ->filter(fn ($question) => filled($question->difficulty_level))
            ->unique('id');

        $correctQuestionIds = $session->attempts
            ->flatMap(fn (Attempt $attempt) => $attempt->answers)
            ->where('is_correct', true)
            ->pluck('question_id')
            ->unique();

        $levelOrder = config('testing.level_order', ['A1', 'A2', 'B1', 'B2', 'C1', 'C2']);
        $thresholds = config('testing.level_thresholds', []);

        $stats = [];

        foreach ($levelOrder as $level) {
            $levelQuestions = $questions->filter(
                fn ($question) => strtoupper((string) $question->difficulty_level) === $level
            );

            $total = $levelQuestions->count();
            $correct = $levelQuestions
                ->whereIn('id', $correctQuestionIds)
                ->count();
            $percent = $total > 0 ? round(($correct / $total) * 100, 2) : 0;

            $stats[$level] = [
                'total' => $total,
                'correct' => $correct,
                'percent' => $percent,
                'passed' => $total > 0 && $percent >= ($thresholds[$level] ?? 60),
            ];
        }

        $detectedLevel = null;

        foreach ($levelOrder as $level) {
            if (($stats[$level]['total'] ?? 0) === 0) {
                continue;
            }

            if (($stats[$level]['passed'] ?? false) === true) {
                $detectedLevel = $level;
            }
        }

        return [
            'detected_level' => $detectedLevel ?? 'A1',
            'level_stats' => $stats,
        ];
    }
}
