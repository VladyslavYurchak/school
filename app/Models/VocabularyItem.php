<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VocabularyItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'language_id',
        'term',
        'translation',
        'transcription',
        'part_of_speech',
        'explanation',
        'example',
        'example_translation',
        'audio_path',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function lessons()
    {
        return $this->belongsToMany(Lesson::class, 'lesson_vocabulary_items')
            ->withPivot(['is_required', 'note', 'position'])
            ->withTimestamps();
    }

    public function userProgress()
    {
        return $this->hasMany(UserVocabularyProgress::class);
    }
}
