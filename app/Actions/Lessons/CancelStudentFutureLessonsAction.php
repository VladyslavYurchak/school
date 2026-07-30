<?php

namespace App\Actions\Lessons;

use App\Enums\LessonStatus;
use App\Models\LessonLog;
use App\Models\PlannedLesson;
use App\Services\LessonActionLogger;
use Illuminate\Support\Facades\DB;

class CancelStudentFutureLessonsAction
{
    public function handle(PlannedLesson $selectedLesson): int
    {
        if (! $selectedLesson->student_id || $selectedLesson->group_id) {
            return 0;
        }

        return DB::transaction(function () use ($selectedLesson) {
            $lessons = PlannedLesson::query()
                ->where('teacher_id', $selectedLesson->teacher_id)
                ->where('student_id', $selectedLesson->student_id)
                ->whereNull('group_id')
                ->where('status', LessonStatus::Planned->value)
                ->where('start_date', '>=', $selectedLesson->start_date)
                ->orderBy('start_date')
                ->lockForUpdate()
                ->get();

            foreach ($lessons as $lesson) {
                $lesson->update(['status' => LessonStatus::Cancelled]);

                LessonActionLogger::log(
                    lessonId: $lesson->id,
                    action: 'cancelled',
                    lessonDatetime: $lesson->start_date,
                    meta: [
                        'reason' => 'student_future_cancel',
                        'source_lesson_id' => $selectedLesson->id,
                    ],
                );

                LessonLog::query()
                    ->where('lesson_id', $lesson->id)
                    ->delete();

                $lesson->delete();
            }

            return $lessons->count();
        });
    }
}
