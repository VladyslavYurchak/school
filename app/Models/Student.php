<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Teacher;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
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
        'subscription_id'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'start_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Відношення до викладача
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function group() {
        return $this->belongsTo(Group::class);
    }

    public function subscriptionTemplate()
    {
        return $this->belongsTo(SubscriptionTemplate::class, 'subscription_id');
    }

    // Повне імʼя студента
    public function getFullNameAttribute()
    {
        return "{$this->last_name} {$this->first_name}";
    }

    public function getTotalLessonsAttendedAttribute()
    {
        // Припускаю, що в таблиці lesson_logs є поле student_id, яке пов'язує запис з учнем
        return \DB::table('lesson_logs')
            ->where('student_id', $this->id)
            ->count();
    }

    public function lessonLogs()
    {
        return $this->hasMany(\App\Models\LessonLog::class);
    }


    public function getTotalEarningsAttribute()
    {
        return $this->subscriptions()->sum('price');
    }

}

