<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramPaymentConfirmation extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'payment_id',
        'telegram_account_id',
        'status',
        'attempts',
        'last_attempt_at',
        'sent_at',
    ];

    protected $casts = [
        'last_attempt_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function telegramAccount()
    {
        return $this->belongsTo(TelegramAccount::class);
    }
}
