<?php

namespace App\Services\Telegram;

use App\Enums\LessonLogStatus;
use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Models\LessonLog;
use App\Models\PlannedLesson;
use App\Models\Teacher;
use App\Services\Calendar\CalendarAvailabilityService;
use App\Services\LessonActionLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TelegramTeacherLessonService
{
    public function __construct(
        private readonly CalendarAvailabilityService $availability,
    ) {}

    public function complete(int $lessonId, Teacher $teacher, int $userId): PlannedLesson
    {
        return DB::transaction(function () use ($lessonId, $teacher, $userId) {
            $lesson = $this->ownedPlannedLesson($lessonId, $teacher, true);
            $lessonStart = $lesson->start_date;

            $lesson->update(['status' => LessonStatus::Completed]);

            if ($lesson->group_id) {
                $this->recordGroupAttendance($lesson, $teacher);
            } else {
                $this->recordIndividualAttendance($lesson, $teacher);
            }

            LessonActionLogger::log(
                lessonId: $lesson->id,
                action: 'completed',
                lessonDatetime: $lessonStart,
                meta: [
                    'source' => 'telegram',
                    'all_group_students_present' => (bool) $lesson->group_id,
                ],
                userId: $userId,
            );

            return $lesson;
        });
    }

    public function cancel(int $lessonId, Teacher $teacher, int $userId): PlannedLesson
    {
        return DB::transaction(function () use ($lessonId, $teacher, $userId) {
            $lesson = $this->ownedPlannedLesson($lessonId, $teacher, true);
            $lessonStart = $lesson->start_date;

            $lesson->update(['status' => LessonStatus::Cancelled]);
            LessonLog::query()->where('lesson_id', $lesson->id)->delete();

            LessonActionLogger::log(
                lessonId: $lesson->id,
                action: 'cancelled',
                lessonDatetime: $lessonStart,
                meta: [
                    'source' => 'telegram',
                    'reason' => 'manual_cancel',
                ],
                userId: $userId,
            );

            $lesson->delete();

            return $lesson;
        });
    }

    public function reschedule(
        int $lessonId,
        Teacher $teacher,
        int $userId,
        CarbonImmutable $newStart,
    ): PlannedLesson {
        return DB::transaction(function () use ($lessonId, $teacher, $userId, $newStart) {
            $lesson = $this->ownedPlannedLesson($lessonId, $teacher, true);
            $oldStart = CarbonImmutable::parse($lesson->start_date)->seconds(0);
            $newStart = $newStart->seconds(0);

            if ($oldStart->equalTo($newStart)) {
                throw new RuntimeException('Нові дата і час збігаються з поточними.');
            }

            $duration = max(
                15,
                (int) ($lesson->duration ?: $oldStart->diffInMinutes($lesson->end_date)),
            );
            $newEnd = $newStart->addMinutes($duration);

            if ($this->availability->teacherHasOverlap($teacher->id, $newStart, $newEnd, $lesson->id)) {
                throw new RuntimeException('У викладача вже є інше заняття в цей час.');
            }

            if (
                $lesson->student_id
                && $this->availability->studentHasOverlap(
                    $lesson->student_id,
                    $newStart,
                    $newEnd,
                    $lesson->id,
                )
            ) {
                throw new RuntimeException('В учня вже є інше заняття в цей час.');
            }

            if (
                $lesson->group_id
                && $this->availability->groupHasOverlap(
                    $lesson->group_id,
                    $newStart,
                    $newEnd,
                    $lesson->id,
                )
            ) {
                throw new RuntimeException('У групи вже є інше заняття в цей час.');
            }

            $lesson->update([
                'status' => LessonStatus::Rescheduled,
                'initiator' => 'teacher',
            ]);
            LessonLog::query()->where('lesson_id', $lesson->id)->delete();

            $newLesson = PlannedLesson::query()->create([
                'title' => $lesson->title,
                'student_id' => $lesson->student_id,
                'teacher_id' => $lesson->teacher_id,
                'group_id' => $lesson->group_id,
                'start_date' => $newStart,
                'end_date' => $newEnd,
                'status' => LessonStatus::Planned,
                'initiator' => null,
                'lesson_type' => $lesson->lesson_type ?? LessonType::Individual,
                'notes' => $lesson->notes,
                'meeting_url' => $lesson->meeting_url,
            ]);

            LessonActionLogger::log(
                lessonId: $lesson->id,
                action: 'rescheduled',
                lessonDatetime: $oldStart,
                newLessonDatetime: $newStart,
                meta: [
                    'source' => 'telegram',
                    'initiator' => 'teacher',
                    'new_lesson_id' => $newLesson->id,
                ],
                userId: $userId,
            );

            $lesson->delete();

            return $newLesson;
        });
    }

    private function ownedPlannedLesson(
        int $lessonId,
        Teacher $teacher,
        bool $lock = false,
    ): PlannedLesson {
        $query = PlannedLesson::query()
            ->with(['teacher', 'group.students'])
            ->whereKey($lessonId)
            ->where('teacher_id', $teacher->id)
            ->where('status', LessonStatus::Planned->value);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first()
            ?? throw new RuntimeException('Заняття не знайдено, воно вже змінене або належить іншому викладачеві.');
    }

    private function recordIndividualAttendance(PlannedLesson $lesson, Teacher $teacher): void
    {
        $type = $lesson->lesson_type instanceof LessonType
            ? $lesson->lesson_type
            : LessonType::from((string) $lesson->lesson_type);
        $baseRate = $type === LessonType::Trial
            ? (float) ($teacher->trial_lesson_price ?? 0)
            : (float) ($teacher->lesson_price ?? 0);

        LessonLog::query()->updateOrCreate(
            ['lesson_id' => $lesson->id],
            [
                'student_id' => $lesson->student_id,
                'teacher_id' => $teacher->id,
                'group_id' => null,
                'lesson_type' => $type->value,
                'date' => $lesson->start_date->toDateString(),
                'time' => $lesson->start_date->format('H:i:s'),
                'duration' => max(15, (int) ($lesson->duration ?: 60)),
                'status' => LessonLogStatus::Completed,
                'notes' => $lesson->notes,
                'teacher_rate_amount_at_charge' => $baseRate,
                'teacher_payout_basis' => 'per_lesson',
                'teacher_payout_amount' => round($baseRate, 2),
                'charged_at' => now(),
            ],
        );
    }

    private function recordGroupAttendance(PlannedLesson $lesson, Teacher $teacher): void
    {
        $students = $lesson->group?->students ?? collect();

        if ($students->isEmpty()) {
            throw new RuntimeException('У групі немає учнів, тому заняття не можна відмітити проведеним.');
        }

        $type = $lesson->lesson_type instanceof LessonType
            ? $lesson->lesson_type
            : LessonType::from((string) $lesson->lesson_type);
        $baseRate = $type === LessonType::Pair
            ? (float) ($teacher->pair_lesson_price ?? 0)
            : (float) ($teacher->group_lesson_price ?? 0);
        $totalCents = (int) round($baseRate * 100);
        $shareCents = intdiv($totalCents, $students->count());
        $remainder = $totalCents % $students->count();

        foreach ($students->values() as $index => $student) {
            $payout = ($shareCents + ($index < $remainder ? 1 : 0)) / 100;

            LessonLog::query()->updateOrCreate(
                [
                    'lesson_id' => $lesson->id,
                    'student_id' => $student->id,
                ],
                [
                    'teacher_id' => $teacher->id,
                    'group_id' => $lesson->group_id,
                    'lesson_type' => $type->value,
                    'date' => $lesson->start_date->toDateString(),
                    'time' => $lesson->start_date->format('H:i:s'),
                    'duration' => max(15, (int) ($lesson->duration ?: 60)),
                    'status' => LessonLogStatus::Completed,
                    'notes' => $lesson->notes,
                    'teacher_rate_amount_at_charge' => $baseRate,
                    'teacher_payout_basis' => 'per_lesson',
                    'teacher_payout_amount' => $payout,
                    'charged_at' => now(),
                ],
            );
        }
    }
}
