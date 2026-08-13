<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramHomeworkAssignment extends Model
{
    protected $fillable = [
        'planned_lesson_id',
        'teacher_id',
        'text',
        'telegram_file_id',
        'telegram_file_type',
        'file_name',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function plannedLesson()
    {
        return $this->belongsTo(PlannedLesson::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function submissions()
    {
        return $this->hasMany(TelegramHomeworkSubmission::class);
    }
}
