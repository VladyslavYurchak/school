<?php

namespace App\Models\Testing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $table = 'testing_sections';

    protected $fillable = [
        'test_id',
        'title',
        'description',
        'type',
        'instruction_text',
        'media_type',
        'media_url',
        'media_title',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'section_id')->orderBy('sort_order');
    }
}
