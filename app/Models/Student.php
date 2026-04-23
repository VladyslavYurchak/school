<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'remaining_lessons',
        'remaining_group_lessons',
        'teacher_id',
        'group_id',
        'custom_lesson_price',
        'custom_group_lesson_price',
        'birth_date',
        'parent_contact',
        'balance',
        'is_active',
        'start_date',
        'total_lessons_attended',
        'note',
        'subscription_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'start_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    // legacy, якщо поки ще використовуєш students.subscription_id
    public function subscriptionTemplate()
    {
        return $this->belongsTo(SubscriptionTemplate::class, 'subscription_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(StudentSubscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(StudentSubscription::class)
            ->where('status', 'active')
            ->latestOfMany();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function lessonLogs()
    {
        return $this->hasMany(LessonLog::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->last_name} {$this->first_name}");
    }

    public function getLessonLogsCountAttribute(): int
    {
        return $this->lessonLogs()->count();
    }

    public function getTotalEarningsAttribute(): float
    {
        return (float) $this->subscriptions()->sum('price');
    }
}
