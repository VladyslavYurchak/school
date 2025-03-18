<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'question',
        'is_multiple_choice',
        'correct_answer',
    ];

    /**
     * Відношення до моделі Lesson
     */
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Відношення до варіантів відповідей
     */
    public function options()
    {
        return $this->hasMany(LessonTestOption::class);
    }
}
