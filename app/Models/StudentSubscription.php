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
        'start_date',
        'end_date',
        'price',
        'type', // додано поле типу оплати
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Відношення до студента
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // Відношення до шаблону абонемента (nullable, бо для поразової оплати може бути null)
    public function subscriptionTemplate()
    {
        return $this->belongsTo(SubscriptionTemplate::class);
    }

    // Хелпер: чи є це поразова оплата?
    public function isSinglePayment()
    {
        return $this->type === 'single';
    }

    // Хелпер: чи є це абонемент?
    public function isSubscription()
    {
        return $this->type === 'subscription';
    }
}
