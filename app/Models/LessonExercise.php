<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonExercise extends Model
{
    public const TYPE_MATCHING = 'matching';
    public const TYPE_FILL_BLANK = 'fill_blank';
    public const TYPE_WORD_ORDER = 'word_order';
    public const TYPE_TRANSFORMATION = 'transformation';
    public const TYPE_TRUE_FALSE = 'true_false';
    public const TYPE_DICTATION = 'dictation';

    public const TYPES = [
        self::TYPE_MATCHING,
        self::TYPE_FILL_BLANK,
        self::TYPE_WORD_ORDER,
        self::TYPE_TRANSFORMATION,
        self::TYPE_TRUE_FALSE,
        self::TYPE_DICTATION,
    ];

    public const ANSWER_MODE_TYPING = 'typing';
    public const ANSWER_MODE_CHOICE = 'choice';

    public const ANSWER_MODES = [
        self::ANSWER_MODE_TYPING,
        self::ANSWER_MODE_CHOICE,
    ];

    protected $fillable = [
        'lesson_id',
        'type',
        'title',
        'description',
        'settings',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function items()
    {
        return $this->hasMany(LessonExerciseItem::class)->orderBy('position')->orderBy('id');
    }
}
