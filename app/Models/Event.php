<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'events'; // або інша назва таблиці

    protected $fillable = [
        'teacher_id',
        'title',
        'start',   // дата і час початку заняття (datetime)
        'end',     // дата і час кінця заняття (datetime, опціонально)
        'status',  // наприклад, 'active', 'cancelled'
        'notes',   // додаткові нотатки (опціонально)
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Відношення до вчителя
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
