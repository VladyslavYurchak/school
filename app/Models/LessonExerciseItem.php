<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonExerciseItem extends Model
{
    protected $fillable = [
        'lesson_exercise_id',
        'prompt',
        'answer',
        'settings',
        'audio_path',
        'position',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function exercise()
    {
        return $this->belongsTo(LessonExercise::class, 'lesson_exercise_id');
    }
}
