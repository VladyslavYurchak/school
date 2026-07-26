<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonVocabularyItem extends Model
{
    protected $fillable = [
        'lesson_id',
        'vocabulary_item_id',
        'is_required',
        'note',
        'position',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'position' => 'integer',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function vocabularyItem()
    {
        return $this->belongsTo(VocabularyItem::class);
    }
}
