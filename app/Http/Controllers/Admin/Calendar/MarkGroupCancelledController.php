<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Enums\LessonStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Calendar\MarkGroupCancelledRequest;
use App\Models\LessonLog;
use App\Models\PlannedLesson;
use Carbon\Carbon;
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

                // 0) Обчислюємо точний слот
                $slot = Carbon::parse($data['date'].' '.$data['time']); // припускаємо, що start_date у тій же тайзоні

                // 1) Знаходимо урок за групою і точним часом
                $lessonsQuery = PlannedLesson::query()
                    ->where('group_id', (int)$data['group_id'])
                    ->where('start_date', '=', $slot);

                // Якщо потенційно можливі дублікати — перевіримо кількість
                $candidates = $lessonsQuery->count();
                if ($candidates === 0) {
                    return [
                        'status'  => Response::HTTP_NOT_FOUND,
                        'success' => false,
                        'message' => 'Урок із такою групою та часом не знайдено.',
                    ];
                }
                if ($candidates > 1) {
                    // Це сигнал, що потрібен унікальний індекс або додаткові критерії
                    return [
                        'status'  => Response::HTTP_CONFLICT,
                        'success' => false,
                        'message' => 'Знайдено кілька уроків для цього слота. Уточніть дані або виправте дублікати.',
                    ];
                }

                // 2) Беремо єдиний рядок і лочимо його
                $lesson = PlannedLesson::query()
                    ->where('group_id', (int)$data['group_id'])
                    ->where('start_date', '=', $slot)
                    ->lockForUpdate()
                    ->firstOrFail();

                // 3) Якщо вже скасовано — віддамо дружнє повідомлення
                $wasCancelled = ((string)$lesson->status === LessonStatus::Cancelled->value);

                // 4) Ставимо статус Cancelled
                $lesson->status = LessonStatus::Cancelled->value;
                $lesson->save();

                // 5) Чистимо lesson_logs за групою+датою+часом (бо lesson_id немає)
                $deletedBySlot = LessonLog::query()
                    ->where('group_id', (int)$data['group_id'])
                    ->whereDate('date', $slot->toDateString())
                    ->where('time', $slot->format('H:i'))
                    ->delete();
                $lesson->delete();

                return [
                    'status'  => Response::HTTP_OK,
                    'success' => true,
                    'message' => $wasCancelled
                        ? 'Урок уже був скасований. Журнали очищено повторно.'
                        : 'Групове заняття скасовано, журнали очищено.',
                    'meta'    => [
                        'deleted_logs' => $deletedBySlot,
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
