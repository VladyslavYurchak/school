<?php

declare(strict_types=1);

namespace App\Actions\Lessons;

use App\Enums\LessonStatus;
use App\Exceptions\Domain\LessonNotFound;
use App\Models\LessonLog;
use App\Models\PlannedLesson;
use Illuminate\Support\Facades\DB;

final class CancelGroupLessonAction
{
    public function handle(int $lessonId, int $groupId): array
    {
        return DB::transaction(function () use ($lessonId, $groupId) {
            /** @var PlannedLesson|null $lesson */

            $query = PlannedLesson::query()
                ->whereKey($lessonId)
                ->where('group_id', $groupId);

            $user = auth()->user();

            if ($user->role === 'teacher') {
                $teacherId = optional($user->teacher)->id;

                if (!$teacherId) {
                    abort(403);
                }

                $query->where('teacher_id', $teacherId);
            }

            $lesson = $query
                ->lockForUpdate()
                ->first();

            if (!$lesson) {
                throw new LessonNotFound('Урок не знайдено або недоступний.', [
                    'lesson_id' => $lessonId,
                    'group_id'  => $groupId,
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
                'lesson'            => $lesson,
                'already_cancelled' => $wasCancelled,
                'deleted_logs'      => $deletedLogs,
            ];
        });
    }
}
