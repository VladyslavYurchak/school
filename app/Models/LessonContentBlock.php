<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonContentBlock extends Model
{
    use HasFactory;

    public const TYPE_TEXT = 'text';
    public const TYPE_VIDEO = 'video';
    public const TYPE_AUDIO = 'audio';
    public const TYPE_IMAGE = 'image';
    public const TYPE_PDF = 'pdf';

    public const TYPES = [
        self::TYPE_TEXT,
        self::TYPE_VIDEO,
        self::TYPE_AUDIO,
        self::TYPE_IMAGE,
        self::TYPE_PDF,
    ];

    protected $fillable = [
        'lesson_id',
        'type',
        'title',
        'content',
        'video_url',
        'media_path',
        'media_name',
        'media_mime',
        'media_size',
        'settings',
        'position',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'media_size' => 'integer',
        'position' => 'integer',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function isMedia(): bool
    {
        return in_array($this->type, [self::TYPE_AUDIO, self::TYPE_IMAGE, self::TYPE_PDF], true);
    }
}
