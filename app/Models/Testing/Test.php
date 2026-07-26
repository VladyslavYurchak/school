<?php

namespace App\Models\Testing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

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

    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('is_public', true)
            ->whereHas('sections', function (Builder $sections) {
                $sections
                    ->where('is_active', true)
                    ->whereHas('questions', fn (Builder $questions) => $questions->where('is_active', true));
            })
            ->whereDoesntHave('questions', function (Builder $questions) {
                $questions
                    ->where('is_active', true)
                    ->where(function (Builder $invalid) {
                        $invalid
                            ->whereNull('difficulty_level')
                            ->orWhere(function (Builder $choiceQuestion) {
                                $choiceQuestion
                                    ->whereIn('type', ['single_choice', 'multiple_choice', 'true_false'])
                                    ->whereDoesntHave(
                                        'options',
                                        fn (Builder $options) => $options->where('is_correct', true)
                                    );
                            });
                    });
            });
    }

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
