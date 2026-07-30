<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramAccount extends Model
{
    protected $fillable = [
        'user_id',
        'telegram_user_id',
        'chat_id',
        'username',
        'first_name',
        'notifications_enabled',
        'connected_at',
        'last_interaction_at',
    ];

    protected function casts(): array
    {
        return [
            'notifications_enabled' => 'boolean',
            'connected_at' => 'datetime',
            'last_interaction_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notifications()
    {
        return $this->hasMany(TelegramNotification::class);
    }
}
