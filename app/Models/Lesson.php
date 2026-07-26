<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'description',
        'title',
        'content',
        'lesson_type',
        'position',
        'media_files',
        'video_url',
        'homework_text',
        'audio_file',
        'homework_files',
        'homework_video_url',
        'is_published',
        'price'
    ];

    protected $casts = [
        'media_files' => 'array',
        'homework_files' => 'array',
        'price' => 'decimal:2',
        'is_published' => 'boolean',
    ];


    public function course()
    {
        return $this->belongsTo(Course::class);
    }



    public function tests()
    {
        return $this->hasMany(\App\Models\LessonTest::class)->orderBy('position')->orderBy('id');
    }

    public function contentBlocks()
    {
        return $this->hasMany(LessonContentBlock::class)->orderBy('position')->orderBy('id');
    }

    public function vocabularyItems()
    {
        return $this->belongsToMany(VocabularyItem::class, 'lesson_vocabulary_items')
            ->withPivot(['is_required', 'note', 'position'])
            ->withTimestamps()
            ->orderByPivot('position')
            ->orderBy('vocabulary_items.id');
    }

    public function vocabularyLinks()
    {
        return $this->hasMany(LessonVocabularyItem::class)->orderBy('position')->orderBy('id');
    }

    public function exercises()
    {
        return $this->hasMany(LessonExercise::class)->orderBy('position')->orderBy('id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_lesson')
            ->withPivot('status', 'paid_amount')
            ->withTimestamps();
    }

    public function hasSeparatePrice(): bool
    {
        return $this->price !== null;
    }

    public function isPaid(): bool
    {
        return $this->hasSeparatePrice() && (float) $this->price > 0;
    }

    public function isFree(): bool
    {
        return $this->hasSeparatePrice() && (float) $this->price <= 0;
    }

    public function isAvailableFor(?User $user): bool
    {
        if ($this->isFree()) {
            return true;
        }

        if ($this->course->isAvailableFor($user)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        return $this->users()
            ->where('users.id', $user->id)
            ->wherePivot('status', 'paid')
            ->exists();
    }

    public function hasPurchaseHistory(): bool
    {
        return $this->users()
            ->whereIn('user_lesson.status', ['paid', 'refunded'])
            ->exists()
            || Payment::query()
                ->whereIn('status', ['pending', 'paid', 'refunded'])
                ->where('payload->lesson_id', $this->id)
                ->exists();
    }


}
