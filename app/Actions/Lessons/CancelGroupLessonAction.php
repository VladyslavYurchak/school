<?php

declare(strict_types=1);

namespace App\Actions\Lessons;

use App\Enums\LessonStatus;
use App\Exceptions\Domain\LessonNotFound;
use App\Exceptions\Domain\NotInGroup;
use App\Models\LessonLog;
use App\Models\PlannedLesson;
use Illuminate\Support\Facades\DB;

final class CancelGroupLessonAction
{
    public function handle(int $lessonId, int $groupId): array
    {
        return DB::transaction(function () use ($lessonId, $groupId) {
            /** @var PlannedLesson|null $lesson */
            $lesson = PlannedLesson::query()
                ->lockForUpdate()
                ->find($lessonId);

            if (!$lesson) {
                throw new LessonNotFound('Урок не знайдено.', ['lesson_id' => $lessonId]);
            }

            if ($lesson->group_id !== $groupId) {
                throw new NotInGroup('Заняття не належить зазначеній групі.', [
                    'lesson_id' => $lesson->id,
                    'group_id'  => $groupId,
                    'actual'    => $lesson->group_id,
                ]);
            }

            $wasCancelled = $lesson->status === LessonStatus::Cancelled;

            if (!$wasCancelled) {
                $lesson->status = LessonStatus::Cancelled;
                $lesson->save();
            }

            $deletedLogs = LessonLog::query()
                ->where('lesson_id', $lesson->id)
                ->delete();

            $lesson->delete();

            return [
                'lesson'            => $lesson->fresh(),
                'already_cancelled' => $wasCancelled,
                'deleted_logs'      => $deletedLogs,
            ];
        });
    }
}
