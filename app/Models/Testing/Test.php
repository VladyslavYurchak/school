<?php

namespace App\Models\Testing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Test extends Model
{
    protected $table = 'testing_tests';

    protected $fillable = [
        'title',
        'slug',
        'language_code',
        'description',
        'intro_text',
        'weight',
        'max_score',
        'is_active',
        'is_public',
        'randomize_questions',
        'show_result_immediately',
        'time_limit_minutes',
        'sort_order',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'max_score' => 'decimal:2',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'randomize_questions' => 'boolean',
        'show_result_immediately' => 'boolean',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'test_id')->orderBy('sort_order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'test_id')->orderBy('sort_order');
    }
    public function resultRanges()
    {
        return $this->hasMany(ResultRange::class, 'test_id')->orderBy('min_score');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class, 'test_id');
    }

    public function recalculateMaxScore(): void
    {
        $calculator = app(\App\Services\Testing\ScoreCalculatorService::class);

        $this->update([
            'max_score' => $calculator->calculateTestMaxScore($this),
        ]);
    }
}

