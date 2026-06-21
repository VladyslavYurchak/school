<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'image',
        'start_date',
        'is_published',
    ];

    protected $casts = [
        'start_date' => 'date',
        'is_published' => 'boolean',
    ];

    public function getImageUrlAttribute(): string
    {
        if (! $this->image) {
            return '';
        }

        return Str::startsWith($this->image, ['http://', 'https://'])
            ? $this->image
            : asset('storage/' . $this->image);
    }
}
