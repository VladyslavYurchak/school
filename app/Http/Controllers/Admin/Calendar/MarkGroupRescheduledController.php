<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Calendar\MarkGroupRescheduledRequest;
use App\Models\LessonLog;
use App\Models\PlannedLesson;
use App\Models\Group;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MarkGroupRescheduledController extends Controller
{
    public function __invoke(MarkGroupRescheduledRequest $request)
    {
        $data = $request->validated();

        try {
            DB::transaction(function () use ($data) {
                $lesson = PlannedLesson::findOrFail($data['lesson_id']);
                $group  = Group::findOrFail($data['group_id']);

                $newDateTime = Carbon::parse($data['new_date'] . ' ' . $data['new_time']);

                // Позначаємо старе заняття як перенесене (soft delete, якщо ввімкнено)
                $lesson->status = LessonStatus::Rescheduled->value;
                $lesson->initiator = null; // за потреби, постав того, хто ініціював
                $lesson->save();
                $lesson->delete();

                $start = Carbon::parse($lesson->start_date);
                $end   = Carbon::parse($lesson->end_date);
                $durationMinutes = $lesson->duration ?? $start->diffInMinutes($end);
                $durationMinutes = max(15, $durationMinutes);

                // Створюємо нове заняття
                PlannedLesson::create([
                    'title'       => $lesson->title ?? ($group->name ?? 'Без назви групи'),
                    'teacher_id'  => $lesson->teacher_id,
                    'group_id'    => $group->id,
                    'student_id'  => null,
                    'start_date'  => $newDateTime,
                    'end_date'    => (clone $newDateTime)->addMinutes($durationMinutes),
                    'status'      => LessonStatus::Planned->value,
                    'initiator'   => null,
                    'duration'    => $lesson->duration,
                    'notes'       => $lesson->notes,
                    'lesson_type' => $lesson->lesson_type ?? LessonType::Group->value,
                ]);

                // Видаляємо старий запис з LessonLog (якщо є)
                LessonLog::where('group_id', $group->id)
                    ->whereDate('date', $data['date'])
                    ->whereTime('time', $data['time'])
                    ->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Групове заняття перенесено на нову дату.',
            ]);
        } catch (\Exception $e) {
            Log::error('MarkGroupRescheduledController error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Помилка при перенесенні групового заняття.',
            ], 500);
        }
    }
}
