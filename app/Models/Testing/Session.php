<?php

namespace App\Models\Testing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Session extends Model
{
    protected $table = 'testing_sessions';

    protected $fillable = [
        'language_code',
        'status',
        'total_raw_score',
        'total_weighted_score',
        'max_weighted_score',
        'detected_level',
        'result_range_id',
        'started_at',
        'finished_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'total_raw_score' => 'decimal:2',
        'total_weighted_score' => 'decimal:2',
        'max_weighted_score' => 'decimal:2',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function resultRange(): BelongsTo
    {
        return $this->belongsTo(ResultRange::class, 'result_range_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class, 'session_id');
    }

    public function lead(): HasOne
    {
        return $this->hasOne(Lead::class, 'session_id');
    }
}
