<?php

namespace App\Models;

use App\Enums\LessonStatus;
use App\Enums\LessonType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlannedLesson extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'teacher_id',
        'student_id',
        'group_id',
        'start_date',
        'end_date',
        'status',
        'notes',
        'initiator',
        'lesson_type',
    ];

    protected $casts = [
        'start_date' => 'immutable_datetime',
        'end_date' => 'immutable_datetime',
        'status' => LessonStatus::class,
        'lesson_type' => LessonType::class,
    ];

    public function getDurationAttribute(): ?int
    {
        if (!$this->start_date || !$this->end_date) {
            return null;
        }

        return $this->start_date->diffInMinutes($this->end_date);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function logs()
    {
        return $this->hasMany(LessonLog::class, 'lesson_id');
    }

    public function scopeIntersects($query, CarbonInterface $start, CarbonInterface $end)
    {
        return $query
            ->where('start_date', '<', $end)
            ->where(function ($query) use ($start) {
                $query->where(function ($query) use ($start) {
                    $query->whereNotNull('end_date')
                        ->where('end_date', '>', $start);
                })->orWhere(function ($query) use ($start) {
                    $query->whereNull('end_date')
                        ->where('start_date', '>=', $start);
                });
            });
    }
}
