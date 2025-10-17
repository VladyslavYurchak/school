<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LessonLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',   // ← зв'язок із planned_lessons
        'student_id',
        'group_id',
        'teacher_id',
        'lesson_type',
        'date',
        'time',
        'duration',
        'status',
        'notes',
        'initiator',

        // snapshot оплати
        'teacher_rate_amount_at_charge',
        'teacher_payout_basis',
        'teacher_payout_amount',
        'charged_at',
    ];

    protected $casts = [
        'date'   => 'date',       // Y-m-d
        'charged_at' => 'datetime',
        'teacher_payout_amount' => 'decimal:2',
        'teacher_rate_amount_at_charge' => 'decimal:2',
        'duration' => 'integer',
    ];

    /**
     * Зв'язок із учнем.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Зв'язок із групою.
     */
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    /**
     * Зв'язок із викладачем.
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    /**
     * Зв'язок із запланованим уроком.
     */
    public function lesson()
    {
        return $this->belongsTo(\App\Models\PlannedLesson::class, 'lesson_id');
    }

    /**
     * Отримати статус у вигляді читабельного тексту.
     */
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'completed' => 'Проведено',
            'charged' => 'Списано',
            'rescheduled' => 'Перенесено',
            default => 'Невідомо',
        };
    }

    /**
     * Отримати повну дату і час.
     */
    public function getDatetimeAttribute()
    {
        return "{$this->date} {$this->time}";
    }
}
