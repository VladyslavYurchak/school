<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'language_id',
        'price',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function isPaid(): bool
    {
        return (float) $this->price > 0;
    }

    public function isFree(): bool
    {
        return !$this->isPaid();
    }

    public function isAvailableFor(?User $user): bool
    {
        if ($this->isFree()) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($user->isAdmin() || $user->isTeacher()) {
            return true;
        }

        return $this->users()
            ->where('users.id', $user->id)
            ->wherePivot('status', 'paid')
            ->exists();
    }

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('position', 'asc');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_course')
            ->withPivot('status', 'paid_amount')
            ->withTimestamps();
    }
}
