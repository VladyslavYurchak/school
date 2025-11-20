<?php

namespace App\Http\Resources\Admin\Calendar;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Actions\Calendar\RescheduleGroupLesson\RescheduleResult;

final class RescheduleResultResource extends JsonResource
{
    /** @var RescheduleResult */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'Групове заняття перенесено на нову дату.',
            'meta' => [
                'old_lesson_id' => $this->resource->oldLessonId,
                'new_lesson_id' => $this->resource->newLessonId,
                'deleted_logs'  => $this->resource->deletedLogs,
                'new_start'     => $this->resource->newStart->toDateTimeString(),
                'new_end'       => $this->resource->newEnd->toDateTimeString(),
            ],
        ];
    }
}
