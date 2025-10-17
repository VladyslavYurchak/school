<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Enums\LessonStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Calendar\MarkGroupCancelledRequest;
use App\Models\LessonLog;
use App\Models\PlannedLesson;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MarkGroupCancelledController extends Controller
{
    public function __invoke(MarkGroupCancelledRequest $request)
    {
        $data = $request->validated();

        try {
            $result = DB::transaction(function () use ($data) {

                // 1) Беремо конкретний урок по ID і (опційно) звіряємо, що він належить групі
                /** @var \App\Models\PlannedLesson $lesson */
                $lesson = PlannedLesson::query()
                    ->whereKey((int)$data['lesson_id'])
                    ->when(isset($data['group_id']), fn($q) => $q->where('group_id', (int)$data['group_id']))
                    ->lockForUpdate()
                    ->first();

                if (!$lesson) {
                    return [
                        'status'  => Response::HTTP_NOT_FOUND,
                        'success' => false,
                        'message' => 'Урок із таким ID не знайдено або не належить зазначеній групі.',
                    ];
                }

                // 2) Якщо вже скасовано — просто повідомимо
                $wasCancelled = ((string)$lesson->status === LessonStatus::Cancelled->value);

                // 3) Ставимо статус Cancelled (можеш не видаляти сам урок — історія корисна)
                $lesson->status = LessonStatus::Cancelled->value;
                $lesson->save();
                $lesson->delete();

                // 4) Видаляємо всі логи САМЕ цього уроку (а не всього слота)
                $deletedLogs = LessonLog::query()
                    ->where('lesson_id', $lesson->id)
                    ->delete();

                // (опційно) Якщо в тебе бізнес-логіка вимагає — можеш видаляти урок:
                // $lesson->delete();

                return [
                    'status'  => Response::HTTP_OK,
                    'success' => true,
                    'message' => $wasCancelled
                        ? 'Урок уже був скасований. Журнали цього уроку очищено повторно.'
                        : 'Групове заняття скасовано, журнали цього уроку очищено.',
                    'meta'    => [
                        'deleted_logs' => $deletedLogs,
                        'lesson_id' => $lesson->id,
                    ],
                ];
            });

            return response()->json(
                ['success' => $result['success'], 'message' => $result['message'], 'meta' => $result['meta'] ?? null],
                $result['status']
            );

        } catch (\Throwable $e) {
            Log::error('MarkGroupCancelledController error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            $payload = [
                'success' => false,
                'message' => 'Помилка при скасуванні групового заняття.',
            ];

            if (config('app.debug')) {
                $payload['error'] = $e->getMessage();
                $payload['trace_hint'] = substr($e->getTraceAsString(), 0, 600);
            }

            return response()->json($payload, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
