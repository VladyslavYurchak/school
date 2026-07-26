<?php

namespace App\Models\Testing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Session extends Model
{
    protected $table = 'testing_sessions';

    protected $fillable = [
        'public_token',
        'language_code',
        'status',
        'current_step',
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
        'current_step' => 'integer',
        'total_raw_score' => 'decimal:2',
        'total_weighted_score' => 'decimal:2',
        'max_weighted_score' => 'decimal:2',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Session $session): void {
            $session->public_token ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_token';
    }

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
