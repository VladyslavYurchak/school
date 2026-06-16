<?php

namespace App\Models;

use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, Filterable, SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'image',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];
}
