<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'language_id',
        'price',
        'is_published'
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('position', 'asc');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_course')
            ->withPivot('status', 'paid_amount')
            ->withTimestamps();
    }
}
