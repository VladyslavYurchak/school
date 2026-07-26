<?php

namespace App\Services\Vocabulary;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Models\VocabularyItem;
use Illuminate\Database\Eloquent\Builder;

class AccessibleVocabularyService
{
    public function lessonsQuery(User $user): Builder
    {
        return Lesson::query()
            ->where('lessons.is_published', true)
            ->whereHas('course', fn (Builder $query) => $query->where('is_published', true))
            ->where(function (Builder $query) use ($user) {
                $query
                    ->where(function (Builder $freeLesson) {
                        $freeLesson
                            ->whereNotNull('lessons.price')
                            ->where('lessons.price', '<=', 0);
                    })
                    ->orWhereHas('course', fn (Builder $course) => $course->where('price', '<=', 0))
                    ->orWhereHas('course.users', function (Builder $users) use ($user) {
                        $users
                            ->where('users.id', $user->id)
                            ->where('user_course.status', 'paid');
                    })
                    ->orWhereHas('users', function (Builder $users) use ($user) {
                        $users
                            ->where('users.id', $user->id)
                            ->where('user_lesson.status', 'paid');
                    });
            });
    }

    public function itemsQuery(User $user, ?int $courseId = null): Builder
    {
        $lessonIds = $this->lessonsQuery($user)->select('lessons.id');

        return VocabularyItem::query()
            ->whereHas('lessons', function (Builder $lessons) use ($lessonIds, $courseId) {
                $lessons
                    ->whereIn('lessons.id', $lessonIds)
                    ->when($courseId, fn (Builder $query) => $query->where('lessons.course_id', $courseId));
            });
    }

    public function courses(User $user)
    {
        $lessonIds = $this->lessonsQuery($user)->select('lessons.id');

        return Course::query()
            ->with('language')
            ->where('is_published', true)
            ->whereHas('lessons', fn (Builder $lessons) => $lessons
                ->whereIn('lessons.id', $lessonIds)
                ->whereHas('vocabularyItems'))
            ->orderBy('title')
            ->get();
    }

    public function userCanAccess(User $user, VocabularyItem $item): bool
    {
        return $this->itemsQuery($user)
            ->whereKey($item->getKey())
            ->exists();
    }
}
