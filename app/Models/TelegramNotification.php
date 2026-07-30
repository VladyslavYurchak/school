<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramNotification extends Model
{
    public const TYPE_LESSON_REMINDER = 'lesson_reminder';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'telegram_account_id',
        'planned_lesson_id',
        'type',
        'status',
        'attempts',
        'last_attempt_at',
        'sent_at',
    ];

    protected $casts = [
        'last_attempt_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function telegramAccount()
    {
        return $this->belongsTo(TelegramAccount::class);
    }

    public function plannedLesson()
    {
        return $this->belongsTo(PlannedLesson::class);
    }
}
