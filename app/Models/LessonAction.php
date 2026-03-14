<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'user_id',
        'action',
        'lesson_datetime',
        'new_lesson_datetime',
        'meta',
    ];

    protected $casts = [
        'lesson_datetime'      => 'datetime',
        'new_lesson_datetime'  => 'datetime',
        'meta'                 => 'array',
    ];

    public function lesson()
    {
        return $this->belongsTo(PlannedLesson::class, 'lesson_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
