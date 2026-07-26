<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'teacher_id',
        'subscription_template_id',
        'subscription_title',
        'lesson_type',
        'subscription_lessons_per_week',
        'payment_id',
        'start_date',
        'end_date',
        'price',
        'type',
        'status',
        'lessons_total',
        'lessons_used',
        'paid_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (StudentSubscription $subscription) {
            if ($subscription->teacher_id === null && $subscription->student_id !== null) {
                $subscription->teacher_id = Student::query()
                    ->whereKey($subscription->student_id)
                    ->value('teacher_id');
            }

            if ($subscription->subscription_template_id !== null) {
                $template = SubscriptionTemplate::query()
                    ->find($subscription->subscription_template_id);

                $subscription->subscription_title ??= $template?->title;
                $subscription->lesson_type ??= $template?->type;
                $subscription->subscription_lessons_per_week ??= $template?->lessons_per_week;
            } elseif ($subscription->type === 'single') {
                $subscription->lesson_type ??= 'individual';
            }
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function subscriptionTemplate()
    {
        return $this->belongsTo(SubscriptionTemplate::class, 'subscription_template_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function isSinglePayment(): bool
    {
        return $this->type === 'single';
    }

    public function isSubscription(): bool
    {
        return $this->type === 'subscription';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getLessonsRemainingAttribute(): int
    {
        return max(0, $this->lessons_total - $this->lessons_used);
    }

    public function hasRecordedLessons(): bool
    {
        return LessonLog::query()
            ->where('student_id', $this->student_id)
            ->whereBetween('date', [
                $this->start_date->toDateString(),
                $this->end_date->toDateString(),
            ])
            ->whereIn('status', ['completed', 'charged'])
            ->when(
                $this->lesson_type,
                fn ($query, $lessonType) => $query->where('lesson_type', $lessonType)
            )
            ->exists();
    }
}
