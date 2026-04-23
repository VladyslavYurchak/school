<?php

namespace App\Models\Testing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResultRange extends Model
{
    protected $table = 'testing_result_ranges';

    protected $fillable = [
        'test_id',
        'title',
        'level_code',
        'min_score',
        'max_score',
        'description',
        'recommendation_text',
    ];

    protected $casts = [
        'min_score' => 'decimal:2',
        'max_score' => 'decimal:2',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'result_range_id');
    }
}
