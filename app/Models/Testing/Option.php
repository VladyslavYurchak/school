<?php

namespace App\Models\Testing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Option extends Model
{
    protected $table = 'testing_options';

    protected $fillable = [
        'question_id',
        'option_text',
        'option_value',
        'is_correct',
        'points',
        'explanation',
        'sort_order',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'points' => 'decimal:2',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
