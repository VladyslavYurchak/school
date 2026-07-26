<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserVocabularyProgress extends Model
{
    public const STATUS_LEARNING = 'learning';

    public const STATUS_KNOWN = 'known';

    protected $table = 'user_vocabulary_progress';

    protected $fillable = [
        'user_id',
        'vocabulary_item_id',
        'status',
        'correct_answers',
        'incorrect_answers',
        'correct_streak',
        'learned_at',
        'last_reviewed_at',
        'next_review_at',
    ];

    protected $casts = [
        'correct_answers' => 'integer',
        'incorrect_answers' => 'integer',
        'correct_streak' => 'integer',
        'learned_at' => 'datetime',
        'last_reviewed_at' => 'datetime',
        'next_review_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vocabularyItem()
    {
        return $this->belongsTo(VocabularyItem::class);
    }

    public function markLearning(): void
    {
        $this->forceFill([
            'status' => self::STATUS_LEARNING,
            'correct_streak' => 0,
            'next_review_at' => null,
        ])->save();
    }

    public function markKnown(): void
    {
        $this->forceFill([
            'status' => self::STATUS_KNOWN,
            'learned_at' => $this->learned_at ?? now(),
            'next_review_at' => now(),
        ])->save();
    }

    public function recordReview(bool $isCorrect): void
    {
        $now = now();

        if ($isCorrect) {
            $streak = $this->correct_streak + 1;
            $reviewDays = match (true) {
                $streak >= 5 => 30,
                $streak === 4 => 14,
                $streak === 3 => 7,
                $streak === 2 => 3,
                default => 1,
            };

            $this->forceFill([
                'status' => self::STATUS_KNOWN,
                'correct_answers' => $this->correct_answers + 1,
                'correct_streak' => $streak,
                'learned_at' => $this->learned_at ?? $now,
                'last_reviewed_at' => $now,
                'next_review_at' => $now->copy()->addDays($reviewDays),
            ])->save();

            return;
        }

        $this->forceFill([
            'status' => self::STATUS_KNOWN,
            'incorrect_answers' => $this->incorrect_answers + 1,
            'correct_streak' => 0,
            'learned_at' => $this->learned_at ?? $now,
            'last_reviewed_at' => $now,
            'next_review_at' => $now->copy()->addMinutes(5),
        ])->save();
    }
}
