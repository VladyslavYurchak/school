<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'subscription_template_id',
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

    public function student()
    {
        return $this->belongsTo(Student::class);
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
}
