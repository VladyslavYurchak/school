<?php

namespace App\Services\Testing;

use App\Models\Testing\Attempt;
use App\Models\Testing\Session;

class LevelDetectorService
{
    public function detectFromSession(Session $session): array
    {
        $session->loadMissing([
            'attempts.answers.question',
        ]);

        $answers = $session->attempts
            ->flatMap(fn (Attempt $attempt) => $attempt->answers)
            ->filter(fn ($answer) => $answer->question && $answer->question->difficulty_level);

        $levelOrder = config('testing.level_order', ['A1', 'A2', 'B1', 'B2', 'C1', 'C2']);
        $thresholds = config('testing.level_thresholds', []);

        $stats = [];

        foreach ($levelOrder as $level) {
            $levelAnswers = $answers->filter(
                fn ($answer) => strtoupper((string) $answer->question->difficulty_level) === $level
            );

            $total = $levelAnswers->count();
            $correct = $levelAnswers->where('is_correct', true)->count();
            $percent = $total > 0 ? round(($correct / $total) * 100, 2) : 0;

            $stats[$level] = [
                'total' => $total,
                'correct' => $correct,
                'percent' => $percent,
                'passed' => $total > 0 && $percent >= ($thresholds[$level] ?? 60),
            ];
        }

        $detectedLevel = 'A1';

        foreach ($levelOrder as $level) {
            if (($stats[$level]['total'] ?? 0) === 0) {
                break;
            }

            if (($stats[$level]['passed'] ?? false) === true) {
                $detectedLevel = $level;
            } else {
                break;
            }
        }

        return [
            'detected_level' => $detectedLevel,
            'level_stats' => $stats,
        ];
    }
}
