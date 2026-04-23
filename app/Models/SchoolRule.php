<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolRule extends Model
{
    protected $fillable = [
        'title',
        'content',
        'sort_order',
        'is_active',
    ];
}
