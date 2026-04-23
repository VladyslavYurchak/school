<?php

namespace App\Models\Testing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    protected $table = 'testing_leads';

    protected $fillable = [
        'session_id',
        'name',
        'phone',
        'email',
        'telegram',
        'age',
        'preferred_study_format',
        'notes',
        'contact_consent',
    ];

    protected $casts = [
        'contact_consent' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }
}
