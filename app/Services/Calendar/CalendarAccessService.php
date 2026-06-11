<?php

namespace App\Services\Calendar;

use App\Models\Group;
use App\Models\PlannedLesson;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CalendarAccessService
{
    public function teacherIdFor(User $user): ?int
    {
        return $user->role === 'teacher'
            ? optional($user->teacher)->id
            : null;
    }

    public function scopeGroupForUser(Builder $query, User $user): Builder
    {
        $teacherId = $this->teacherIdFor($user);

        if ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }

        return $query;
    }

    public function studentBelongsToTeacher(int $studentId, int $teacherId): bool
    {
        return Student::query()
            ->whereKey($studentId)
            ->where('teacher_id', $teacherId)
            ->exists();
    }

    public function groupBelongsToTeacher(int $groupId, int $teacherId): bool
    {
        return Group::query()
            ->whereKey($groupId)
            ->where('teacher_id', $teacherId)
            ->exists();
    }

    public function lessonBelongsToTeacher(int $lessonId, int $teacherId): bool
    {
        return PlannedLesson::query()
            ->whereKey($lessonId)
            ->where('teacher_id', $teacherId)
            ->exists();
    }
}
