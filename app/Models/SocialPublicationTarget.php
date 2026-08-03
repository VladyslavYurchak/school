<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPublicationTarget extends Model
{
    protected $fillable = [
        'platform',
        'status',
        'provider_post_id',
        'error_message',
        'provider_response',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'provider_response' => 'array',
            'attempted_at' => 'datetime',
        ];
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(SocialPublication::class, 'social_publication_id');
    }
}
