<?php

// app/Http/Resources/CalendarEventResource.php
declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\LessonStatus;
use App\Enums\LessonType;

final class CalendarEventResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var \App\Models\PlannedLesson $lesson */
        $lesson = $this->resource;

        $studentLabel = $lesson->student
            ? trim(($lesson->student->last_name ?? '') . ' ' . ($lesson->student->first_name ?? ''))
            : '';

        $group      = $lesson->group;
        $groupLabel = $group->name ?? '';
        $baseTitle  = $studentLabel ?: ($groupLabel ?: ($lesson->title ?? 'Заняття'));


        $title = match ($lesson->lesson_type) {
                LessonType::Trial => 'Пробне: ' . $baseTitle,
                LessonType::Group => 'Група: '  . ($groupLabel ?: $baseTitle),
                LessonType::Pair  => 'Пара: '   . ($groupLabel ?: $baseTitle),
                default           => $baseTitle,
            };

        $bg = match ($lesson->status) {
            LessonStatus::Completed   => '#198754',
            LessonStatus::Rescheduled => '#ffc107',
            LessonStatus::Cancelled   => '#dc3545',
            LessonStatus::Planned     => '#6c757d',
            default                   => '#0d6efd',
        };

        return [
            'id'              => $lesson->id,
            'title'           => $title,
            'start'           => $lesson->start_date?->toIso8601String(),
            'end'             => $lesson->end_date?->toIso8601String(),
            'allDay'          => false,
            'backgroundColor' => $bg,
            'extendedProps'   => [
                'lesson_id'   => $lesson->id,
                'lesson_type' => $lesson->lesson_type?->value, // або toString()
                'status'      => $lesson->status?->value,
                'group_id'    => $lesson->group_id,
                'members'     => $group?->relationLoaded('students')
                    ? $group->students->map(fn($s) => [
                        'id'   => $s->id,
                        'name' => trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')),
                    ])->values()->all()
                    : [],
            ],
        ];
    }
}
