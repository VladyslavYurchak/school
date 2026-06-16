<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'description',
        'title',
        'content',
        'lesson_type',
        'position',
        'media_files',
        'video_url',
        'homework_text',
        'audio_file',
        'homework_files',
        'homework_video_url',
        'is_published',
        'price'
    ];

    protected $casts = [
        'media_files' => 'array',
        'homework_files' => 'array',
        'price' => 'decimal:2',
        'is_published' => 'boolean',
    ];


    public function course()
    {
        return $this->belongsTo(Course::class);
    }



    public function tests()
    {
        return $this->hasMany(\App\Models\LessonTest::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_lesson')
            ->withPivot('status', 'paid_amount')
            ->withTimestamps();
    }

    public function isAvailableFor(?User $user): bool
    {
        if ($this->course->isAvailableFor($user)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        return $this->users()
            ->where('users.id', $user->id)
            ->wherePivot('status', 'paid')
            ->exists();
    }



}
