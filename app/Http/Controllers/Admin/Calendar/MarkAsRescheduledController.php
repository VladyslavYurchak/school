<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Calendar\MarkAsRescheduledRequest;
use App\Models\LessonLog;
use App\Models\PlannedLesson;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class MarkAsRescheduledController extends Controller
{
    public function __invoke($id, MarkAsRescheduledRequest $request)
    {
        $data = $request->validated();

        try {
            $result = DB::transaction(function () use ($id, $data) {
                /** @var \App\Models\PlannedLesson|null $lesson */
                $lesson = PlannedLesson::query()
                    ->whereKey((int)$id)
                    ->lockForUpdate()
                    ->first();

                if (!$lesson) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Заняття з таким ID не знайдено.',
                    ], Response::HTTP_NOT_FOUND);
                }

                // цей контролер лише для індивідуальних/пробних
                if (!is_null($lesson->group_id)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Цей ендпойнт призначений для індивідуальних/пробних. Для груп/пар — використай груповий контролер перенесення.',
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $initiator   = $data['initiator']; // teacher|student|admin
                $newDateTime = Carbon::parse($data['new_date'].' '.$data['new_time']);

                // 🔢 Ліміт переносів для студента (2/місяць по новій даті)
                if ($initiator === 'student' && $lesson->student_id) {
                    $reschedulesThisMonth = PlannedLesson::withTrashed()
                        ->where('student_id', $lesson->student_id)
                        ->where('status', LessonStatus::Rescheduled->value)
                        ->where('initiator', 'student')
                        ->whereBetween('start_date', [
                            $newDateTime->copy()->startOfMonth(),
                            $newDateTime->copy()->endOfMonth(),
                        ])
                        ->count();

                    if ($reschedulesThisMonth >= 2) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Учень вже використав ліміт на 2 переноси цього місяця.',
                        ], Response::HTTP_FORBIDDEN);
                    }
                }

                // 📌 Позначаємо старе заняття як перенесене
                $lesson->update([
                    'status'    => LessonStatus::Rescheduled->value,
                    'initiator' => $initiator,
                ]);

                // 🧹 Чистимо всі журнали САМЕ цього уроку
                $deletedLogs = LessonLog::query()
                    ->where('lesson_id', $lesson->id)
                    ->delete();

                // ⏱️ Тривалість
                $start  = Carbon::parse($lesson->start_date);
                $end    = Carbon::parse($lesson->end_date);
                $durMin = $lesson->duration ?? $start->diffInMinutes($end);
                $durMin = max(15, $durMin);

                // 🆕 Створюємо нове заняття (копіюємо корисні поля)
                PlannedLesson::create([
                    'title'       => $lesson->title,
                    'student_id'  => $lesson->student_id,
                    'teacher_id'  => $lesson->teacher_id,
                    'group_id'    => $lesson->group_id,
                    'start_date'  => $newDateTime,
                    'end_date'    => (clone $newDateTime)->addMinutes($durMin),
                    'status'      => \App\Enums\LessonStatus::Planned->value,      // 👈 скаляр
                    'initiator'   => null,
                    'lesson_type' => $lesson->lesson_type?->value                  // 👈 не об’єкт
                        ?? \App\Enums\LessonType::Individual->value,
                    'duration'    => $lesson->duration ?? $durMin,
                    'notes'       => $lesson->notes,
                ]);


                // 🗑️ Soft delete старого (якщо ввімкнено SoftDeletes)
                $lesson->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Заняття перенесено на нову дату.',
                    'meta'    => [
                        'old_lesson_id' => $lesson->id,
                        'deleted_logs'  => $deletedLogs,
                    ],
                ]);
            });

            return $result;

        } catch (\Throwable $e) {
            Log::error('MarkAsRescheduledController error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'lesson_id' => $id,
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Помилка при перенесенні заняття.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
