<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialPublication extends Model
{
    protected $fillable = [
        'created_by',
        'title',
        'caption',
        'media_path',
        'media_type',
        'status',
        'last_published_at',
    ];

    protected function casts(): array
    {
        return [
            'last_published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(SocialPublicationTarget::class);
    }
}
