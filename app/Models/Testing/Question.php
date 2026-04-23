<?php

namespace App\Models\Testing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $table = 'testing_questions';

    protected $fillable = [
        'test_id',
        'section_id',
        'type',
        'title',
        'question_text',
        'helper_text',
        'content_before',
        'content_after',
        'default_correct_points',
        'default_incorrect_points',
        'difficulty_level',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'default_correct_points' => 'decimal:2',
        'default_incorrect_points' => 'decimal:2',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class, 'question_id')->orderBy('sort_order');
    }
}
