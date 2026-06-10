<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonTestAttemptAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_test_attempt_id',
        'lesson_test_id',
        'selected_option_ids',
        'answer_text',
        'is_correct',
    ];

    protected $casts = [
        'selected_option_ids' => 'array',
        'is_correct' => 'boolean',
    ];

    public function attempt()
    {
        return $this->belongsTo(LessonTestAttempt::class, 'lesson_test_attempt_id');
    }

    public function test()
    {
        return $this->belongsTo(LessonTest::class, 'lesson_test_id');
    }
}
