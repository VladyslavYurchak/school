<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramPaymentReminder extends Model
{
    public const STAGE_UPCOMING = 'upcoming';

    public const STAGE_DUE = 'due';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'telegram_account_id',
        'student_id',
        'payment_month',
        'stage',
        'status',
        'attempts',
        'last_attempt_at',
        'sent_at',
    ];

    protected $casts = [
        'last_attempt_at' => 'datetime',
        'sent_at' => 'datetime',
    ];
}
