<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id', 'description', 'title', 'content', 'lesson_type', 'position',
        'media_files', 'video_url', 'homework_text', 'audio_file', 'homework_files', 'homework_video_url'
    ];

    protected $casts = [
        'media_files' => 'array',
        'homework_files' => 'array',
    ];


    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function tests()
    {
        return $this->hasMany(\App\Models\LessonTest::class);
    }

}
