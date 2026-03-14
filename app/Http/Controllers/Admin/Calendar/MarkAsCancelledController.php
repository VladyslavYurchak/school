<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Enums\LessonStatus;
use App\Http\Controllers\Controller;
use App\Models\PlannedLesson;
use App\Models\LessonLog;
use App\Services\LessonActionLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MarkAsCancelledController extends Controller
{
    public function __invoke($id)
    {
        try {
            // беремо урок та одразу блокуємо в транзакції
            $result = DB::transaction(function () use ($id) {
                /** @var \App\Models\PlannedLesson|null $lesson */
                $lesson = PlannedLesson::query()
                    ->whereKey((int)$id)
                    ->lockForUpdate()
                    ->first();

                if (!$lesson) {
                    return [
                        'status'  => Response::HTTP_NOT_FOUND,
                        'success' => false,
                        'message' => 'Заняття з таким ID не знайдено.',
                    ];
                }

                // якщо це групове — користуй груповий ендпойнт
                if (!is_null($lesson->group_id)) {
                    return [
                        'status'  => Response::HTTP_UNPROCESSABLE_ENTITY,
                        'success' => false,
                        'message' => 'Цей ендпойнт призначений для індивідуальних/пробних занять. Для груп використай груповий контролер скасування.',
                    ];
                }

                // вже скасований? — no-op
                if ($lesson->status === LessonStatus::Cancelled) {
                    return [
                        'status'  => Response::HTTP_OK,
                        'success' => true,
                        'message' => 'Заняття вже було скасоване.',
                        'meta'    => ['lesson_id' => $lesson->id],
                    ];
                }

                // ставимо статус Cancelled
                $lesson->status = LessonStatus::Cancelled;
                $lesson->save();

                LessonActionLogger::log(
                    lessonId: $lesson->id,
                    action: 'cancelled',
                    lessonDatetime: $lesson->start_date,
                    newLessonDatetime: null,
                    meta: [
                        'reason' => 'manual_cancel',
                    ]
                );

                // видаляємо усі журнали САМЕ цього уроку
                $deletedLogs = LessonLog::query()
                    ->where('lesson_id', $lesson->id)
                    ->delete();

                // якщо у PlannedLesson увімкнено SoftDeletes — це буде soft delete
                $lesson->delete();

                return [
                    'status'  => Response::HTTP_OK,
                    'success' => true,
                    'message' => 'Заняття скасоване, журнали цього уроку очищено.',
                    'meta'    => [
                        'lesson_id'    => $lesson->id,
                        'deleted_logs' => $deletedLogs,
                    ],
                ];
            });

            return response()->json(
                ['success' => $result['success'], 'message' => $result['message'], 'meta' => $result['meta'] ?? null],
                $result['status']
            );

        } catch (\Throwable $e) {
            Log::error('MarkAsCancelledController error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'lesson_id' => $id,
            ]);

            $payload = [
                'success' => false,
                'message' => 'Помилка при скасуванні заняття.',
            ];

            if (config('app.debug')) {
                $payload['error'] = $e->getMessage();
            }

            return response()->json($payload, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
