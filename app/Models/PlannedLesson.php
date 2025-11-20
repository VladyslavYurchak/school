<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlannedLesson extends Model
{
    use SoftDeletes;
    use HasFactory;


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
        'lesson_type'
    ];

    // app/Models/PlannedLesson.php
    // App/Models/PlannedLesson.php
    protected $casts = [
        'start_date'  => 'immutable_datetime',
        'end_date'    => 'immutable_datetime',
        'status'      => \App\Enums\LessonStatus::class,
        'lesson_type' => \App\Enums\LessonType::class,
    ];

    public function getDurationAttribute(): ?int
    {
        if (!$this->start_date || !$this->end_date) {
            return null;
        }
        // обидва — Carbon завдяки $casts
        return $this->start_date->diffInMinutes($this->end_date);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function student()
    {
        return $this->belongsTo(\App\Models\Student::class); // ✅ не User
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function logs()
    {
        return $this->hasMany(\App\Models\LessonLog::class, 'lesson_id');
    }

    public function scopeIntersects($query, \Carbon\CarbonInterface $start, \Carbon\CarbonInterface $end)
    {
        // [start_date, COALESCE(end_date, start_date)] intersects [start, end)
        return $query
            ->where('start_date', '<', $end)
            ->whereRaw('COALESCE(end_date, start_date) >= ?', [$start]);
    }
}
