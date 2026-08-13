<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramLessonAbsenceRequest extends Model
{
    public const STATUS_REQUESTED = 'requested';

    protected $fillable = [
        'planned_lesson_id',
        'student_id',
        'telegram_account_id',
        'status',
        'requested_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
    ];

    public function plannedLesson()
    {
        return $this->belongsTo(PlannedLesson::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
