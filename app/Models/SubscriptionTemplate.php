<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubscriptionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'lessons_per_week',
        'price',
        'is_active',
    ];

    public function studentSubscriptions()
    {
        return $this->hasMany(StudentSubscription::class, 'subscription_template_id');
    }

    // лишай тільки якщо реально ще використовуєш students.subscription_id
    public function students()
    {
        return $this->hasMany(Student::class, 'subscription_id');
    }
}
