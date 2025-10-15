<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlannedLesson extends Model
{
    use SoftDeletes;

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
    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
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

}
