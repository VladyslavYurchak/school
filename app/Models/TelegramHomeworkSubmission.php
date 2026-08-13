<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramHomeworkSubmission extends Model
{
    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_REVIEWED = 'reviewed';

    protected $fillable = [
        'telegram_homework_assignment_id',
        'student_id',
        'text',
        'telegram_file_id',
        'telegram_file_type',
        'file_name',
        'status',
        'submitted_at',
        'reviewed_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function assignment()
    {
        return $this->belongsTo(
            TelegramHomeworkAssignment::class,
            'telegram_homework_assignment_id',
        );
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
