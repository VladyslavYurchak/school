<?php

namespace App\Services;

use App\Models\LessonAction;
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
        LessonAction::create([
            'lesson_id'           => $lessonId,
            'user_id'             => $userId ?? auth()->id(),
            'action'              => $action,
            'lesson_datetime'     => $lessonDatetime ? Carbon::parse($lessonDatetime) : null,
            'new_lesson_datetime' => $newLessonDatetime ? Carbon::parse($newLessonDatetime) : null,
            'meta'                => $meta,
        ]);
    }
}
