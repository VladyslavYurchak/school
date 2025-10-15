<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LessonLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'group_id',      // додано
        'teacher_id',
        'lesson_type',
        'date',
        'time',
        'duration',
        'status',
        'notes',
        'initiator'
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
