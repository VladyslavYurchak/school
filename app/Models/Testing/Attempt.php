<?php

namespace App\Models\Testing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attempt extends Model
{
    protected $table = 'testing_attempts';

    protected $fillable = [
        'session_id',
        'test_id',
        'status',
        'raw_score',
        'weighted_score',
        'max_score',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'raw_score' => 'decimal:2',
        'weighted_score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class, 'attempt_id');
    }
}
