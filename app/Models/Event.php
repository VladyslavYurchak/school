<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = ['title', 'image'];
    protected $casts = ['start_date' => 'date'];
}
