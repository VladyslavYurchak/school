<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonTestOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_test_id',
        'option_text',
        'is_correct',
    ];

    /**
     * Відношення до моделі LessonTest
     */


    public function test()
    {
        return $this->lessonTest();
    }

    public function lessonTest()
    {
        return $this->belongsTo(LessonTest::class, 'lesson_test_id');
    }
}
