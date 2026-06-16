<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'lessons_per_week',
        'price',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function studentSubscriptions()
    {
        return $this->hasMany(StudentSubscription::class, 'subscription_template_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'subscription_id');
    }
}
