<?php

namespace App\Services;

use App\Models\LessonAction;
use App\Models\PlannedLesson;
use Carbon\Carbon;

class LessonActionLogger
{
    public static function log(
        int     $lessonId,
        string  $action,
        ?string $lessonDatetime = null,
        ?string $newLessonDatetime = null,
        array   $meta = [],
        ?int    $userId = null
    ): void {
        $lesson = PlannedLesson::withTrashed()
            ->with(['student', 'group', 'teacher'])
            ->find($lessonId);

        $lessonType = $lesson?->lesson_type;
        $lessonType = $lessonType instanceof \BackedEnum ? $lessonType->value : $lessonType;

        $snapshot = [
            'lesson_title' => $lesson?->title,
            'lesson_type' => $lessonType,
            'student_id' => $lesson?->student_id,
            'student_name' => $lesson?->student?->full_name,
            'group_id' => $lesson?->group_id,
            'group_name' => $lesson?->group?->name,
            'teacher_id' => $lesson?->teacher_id,
            'teacher_name' => $lesson?->teacher?->full_name,
        ];

        LessonAction::create([
            'lesson_id'           => $lessonId,
            'user_id'             => $userId ?? auth()->id(),
            'action'              => $action,
            'lesson_datetime'     => $lessonDatetime ? Carbon::parse($lessonDatetime) : null,
            'new_lesson_datetime' => $newLessonDatetime ? Carbon::parse($newLessonDatetime) : null,
            'meta'                => array_replace($snapshot, $meta),
        ]);
    }
}
