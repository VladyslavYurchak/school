<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrialLessonRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'preferred_contact',
        'notes',
        'status',
        'contacted_at',
        'contacted_by',
    ];

    protected $casts = [
        'contacted_at' => 'datetime',
    ];

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function markContacted(User $user): void
    {
        $this->update([
            'status' => 'contacted',
            'contacted_at' => now(),
            'contacted_by' => $user->id,
        ]);
    }
}
