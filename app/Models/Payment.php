<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'amount',
        'currency',
        'status',
        'type',
        'provider',
        'provider_payment_id',
        'provider_order_id',
        'description',
        'paid_at',
        'payload',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'payload' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(StudentSubscription::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
