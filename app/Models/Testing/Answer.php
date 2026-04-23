<?php

namespace App\Models\Testing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    protected $table = 'testing_answers';

    protected $fillable = [
        'attempt_id',
        'question_id',
        'selected_option_id',
        'answer_text',
        'is_correct',
        'awarded_points',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'awarded_points' => 'decimal:2',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'selected_option_id');
    }
}
