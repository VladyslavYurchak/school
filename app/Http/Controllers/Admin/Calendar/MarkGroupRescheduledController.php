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
use Symfony\Component\HttpFoundation\Response;

class MarkGroupRescheduledController extends Controller
{
    public function __invoke(MarkGroupRescheduledRequest $request)
    {
        $data = $request->validated();

        try {
            $result = DB::transaction(function () use ($data) {
                // 1) Точно беремо урок і блокуємо, а також перевіряємо групу
                /** @var \App\Models\PlannedLesson|null $lesson */
                $lesson = PlannedLesson::query()
                    ->whereKey((int)$data['lesson_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$lesson) {
                    return [
                        'status'  => Response::HTTP_NOT_FOUND,
                        'success' => false,
                        'message' => 'Урок із таким ID не знайдено.',
                    ];
                }

                /** @var \App\Models\Group|null $group */
                $group = Group::query()->find((int)$data['group_id']);
                if (!$group) {
                    return [
                        'status'  => Response::HTTP_UNPROCESSABLE_ENTITY,
                        'success' => false,
                        'message' => 'Групу не знайдено.',
                    ];
                }

                if ((int)$lesson->group_id !== (int)$group->id) {
                    return [
                        'status'  => Response::HTTP_UNPROCESSABLE_ENTITY,
                        'success' => false,
                        'message' => 'Урок не належить зазначеній групі.',
                    ];
                }

                // 2) Нова дата/час
                $newDateTime = Carbon::parse($data['new_date'] . ' ' . $data['new_time']);

                // 3) Позначаємо старе заняття як перенесене (і видаляємо, якщо увімкнено SoftDeletes)
                $lesson->status    = LessonStatus::Rescheduled->value;
                $lesson->initiator = $lesson->initiator ?? null;
                $lesson->save();
                $lesson->delete(); // soft delete, якщо модель використовує SoftDeletes

                // 4) Тривалість
                $start  = Carbon::parse($lesson->start_date);
                $end    = Carbon::parse($lesson->end_date);
                $durMin = $lesson->duration ?? $start->diffInMinutes($end);
                $durMin = max(15, $durMin);

                // 5) Створюємо НОВИЙ урок (копіюємо важливі поля)
                $newLesson = PlannedLesson::create([
                    'title'       => $lesson->title ?? ($group->name ?? 'Групове заняття'),
                    'teacher_id'  => $lesson->teacher_id,
                    'group_id'    => $group->id,
                    'student_id'  => null,
                    'start_date'  => $newDateTime,
                    'end_date'    => (clone $newDateTime)->addMinutes($durMin),
                    'status'      => LessonStatus::Planned->value,
                    'initiator'   => null,
                    'duration'    => $lesson->duration ?? $durMin,
                    'notes'       => $lesson->notes,
                    'lesson_type' => $lesson->lesson_type ?? LessonType::Group->value,
                ]);

                // 6) Чистимо логи СТАРОГО уроку по lesson_id
                $deletedLogs = LessonLog::query()
                    ->where('lesson_id', $lesson->id)
                    ->delete();

                return [
                    'status'  => Response::HTTP_OK,
                    'success' => true,
                    'message' => 'Групове заняття перенесено на нову дату.',
                    'meta'    => [
                        'old_lesson_id' => $lesson->id,
                        'new_lesson_id' => $newLesson->id,
                        'deleted_logs'  => $deletedLogs,
                        'new_start'     => $newLesson->start_date,
                        'new_end'       => $newLesson->end_date,
                    ],
                ];
            });

            return response()->json(
                ['success' => $result['success'], 'message' => $result['message'], 'meta' => $result['meta'] ?? null],
                $result['status']
            );

        } catch (\Throwable $e) {
            Log::error('MarkGroupRescheduledController error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Помилка при перенесенні групового заняття.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
