<?php

namespace App\Services\Calendar;

use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Models\Group;
use App\Models\LessonLog;
use App\Models\PlannedLesson;
use App\Models\Teacher;
use App\Services\LessonActionLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final class RescheduleGroupLessonService
{
    public function __construct(
        private readonly CalendarAvailabilityService $availability
    ) {}

    /**
     * @param array{
     *   group_id:int,
     *   lesson_id:int,
     *   new_date:string, // Y-m-d
     *   new_time:string, // H:i
     * } $data
     * @return array{
     *   status:int,
     *   success:bool,
     *   message:string,
     *   meta?: array{
     *     old_lesson_id:int,
     *     new_lesson_id:int,
     *     deleted_logs:int,
     *     new_start:string,
     *     new_end:string,
     *   }
     * }
     */
    public function handle(array $data): array
    {
        return DB::transaction(function () use ($data) {

            $user = auth()->user();

            $groupQuery = Group::query()
                ->whereKey((int) $data['group_id']);

            $lessonQuery = PlannedLesson::query()
                ->whereKey((int) $data['lesson_id'])
                ->where('group_id', (int) $data['group_id']);

            if ($user->role === 'teacher') {
                $teacherId = optional($user->teacher)->id;

                if (! $teacherId) {
                    abort(403);
                }

                $groupQuery->where('teacher_id', $teacherId);
                $lessonQuery->where('teacher_id', $teacherId);
            }

            $group = $groupQuery->first();

            if (! $group) {
                return $this->fail(Response::HTTP_UNPROCESSABLE_ENTITY, 'Групу не знайдено або недоступна.');
            }

            Teacher::query()->lockForUpdate()->findOrFail($group->teacher_id);

            $lesson = $lessonQuery
                ->lockForUpdate()
                ->first();

            if (! $lesson) {
                return $this->fail(Response::HTTP_NOT_FOUND, 'Урок із таким ID не знайдено або недоступний.');
            }
            // 2) Нова дата/час
            $newStart = CarbonImmutable::createFromFormat('Y-m-d H:i', $data['new_date'].' '.$data['new_time'])
                ->seconds(0);

            // 3) Стара дата/час
            $oldStart = CarbonImmutable::parse($lesson->start_date)->seconds(0);

            if ($oldStart->equalTo($newStart)) {
                return $this->fail(Response::HTTP_UNPROCESSABLE_ENTITY, 'Нова дата і час співпадають з поточними.');
            }

            // 4) Тривалість
            $duration = (int) ($lesson->duration
                ?? CarbonImmutable::parse($lesson->start_date)->diffInMinutes($lesson->end_date));
            $duration = max(15, $duration);
            $newEnd = $newStart->addMinutes($duration);

            // 5) Перевірка конфліктів
            $hasConflict = $this->availability->groupHasOverlap(
                (int) $group->id,
                $newStart,
                $newEnd,
                (int) $lesson->id
            );

            if ($hasConflict) {
                return $this->fail(
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    'Для цієї групи вже існує інше заняття у вказаний проміжок.'
                );
            }

            $teacherHasConflict = $this->availability->teacherHasOverlap(
                (int) $lesson->teacher_id,
                $newStart,
                $newEnd,
                (int) $lesson->id
            );

            if ($teacherHasConflict) {
                return $this->fail(
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    'Викладач уже має інше заняття у вказаний проміжок.'
                );
            }

            // 6) Позначаємо старий як перенесений + soft-delete
            $lesson->status = LessonStatus::Rescheduled->value;
            $lesson->initiator = $lesson->initiator ?? null;
            $lesson->save();
            $lesson->delete();

            // 7) Створюємо новий урок
            $newLesson = PlannedLesson::create([
                'title' => $lesson->title ?? ($group->name ?? 'Групове заняття'),
                'teacher_id' => $lesson->teacher_id,
                'group_id' => $group->id,
                'student_id' => null,
                'start_date' => $newStart,
                'end_date' => $newEnd,
                'status' => LessonStatus::Planned->value,
                'initiator' => null,
                'duration' => $lesson->duration ?? $duration,
                'notes' => $lesson->notes,
                'meeting_url' => $lesson->meeting_url,
                'lesson_type' => $lesson->lesson_type ?? LessonType::Group->value,
            ]);

            // 8) Чистимо логи старого уроку
            $deletedLogs = LessonLog::query()
                ->where('lesson_id', $lesson->id)
                ->delete();

            // 9) 🔥 ЛОГУЄМО перенесення (ТУТ Є ВСЕ ЩО ТРЕБА)
            LessonActionLogger::log(
                lessonId: $lesson->id,                                   // старий (перенесений) урок
                action: 'rescheduled',
                lessonDatetime: $oldStart->toDateTimeString(),           // з якої дати
                newLessonDatetime: $newStart->toDateTimeString(),        // на яку дату
                meta: [
                    'group_id' => $group->id,
                    'old_lesson_id' => (int) $lesson->id,
                    'new_lesson_id' => (int) $newLesson->id,
                    'deleted_logs' => (int) $deletedLogs,
                ]
            );

            return [
                'status' => Response::HTTP_OK,
                'success' => true,
                'message' => 'Групове заняття перенесено на нову дату.',
                'meta' => [
                    'old_lesson_id' => (int) $lesson->id,
                    'new_lesson_id' => (int) $newLesson->id,
                    'deleted_logs' => (int) $deletedLogs,
                    'new_start' => $newStart->toDateTimeString(),
                    'new_end' => $newEnd->toDateTimeString(),
                ],
            ];
        });
    }

    private function fail(int $status, string $message): array
    {
        return ['status' => $status, 'success' => false, 'message' => $message];
    }
}
