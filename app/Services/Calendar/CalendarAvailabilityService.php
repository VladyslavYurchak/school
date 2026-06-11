<?php

namespace App\Services\Calendar;

use App\Enums\LessonStatus;
use App\Models\PlannedLesson;
use Carbon\CarbonInterface;

class CalendarAvailabilityService
{
    public function teacherHasOverlap(
        int $teacherId,
        CarbonInterface $start,
        CarbonInterface $end,
        ?int $exceptLessonId = null
    ): bool {
        return PlannedLesson::query()
            ->where('teacher_id', $teacherId)
            ->when($exceptLessonId, fn ($query) => $query->where('id', '!=', $exceptLessonId))
            ->whereNotIn('status', [
                LessonStatus::Cancelled->value,
                LessonStatus::Rescheduled->value,
            ])
            ->where(function ($query) use ($start, $end) {
                $query
                    ->where('start_date', '<', $end)
                    ->where('end_date', '>', $start);
            })
            ->exists();
    }
}
