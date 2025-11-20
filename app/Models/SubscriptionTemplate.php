<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubscriptionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type', // 'individual' або 'group' 'pair' 'trial'
        'lessons_per_week',
        'price',
        // якщо description немає в БД — не пишемо її тут
    ];

    // Студенти, які мають цей шаблон
    public function students()
    {
        return $this->hasMany(Student::class, 'subscription_id');
    }

    public function studentsSubscriptions()
    {
        return $this->hasMany(StudentSubscription::class, 'subscription_id');
    }

}
