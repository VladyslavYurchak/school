<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Calendar;

use App\Actions\Lessons\CancelGroupLessonAction;
use App\Enums\LessonStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Calendar\MarkGroupCancelledRequest;
use App\Services\LessonActionLogger;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class MarkGroupCancelledController extends Controller
{
    public function __invoke(
        MarkGroupCancelledRequest $request,
        CancelGroupLessonAction $action
    ): JsonResponse {
        $data   = $request->validated();
        $result = $action->handle((int)$data['lesson_id'], (int)$data['group_id']);

        $lesson = $result['lesson'];

        LessonActionLogger::log(
            lessonId: $lesson->id,
            action: 'cancelled',
            lessonDatetime: $lesson->start_date?->toDateTimeString(), // дата/час уроку на момент скасування
            newLessonDatetime: null,
            meta: [
                'group_id'          => (int)$data['group_id'],
                'already_cancelled' => (bool)$result['already_cancelled'],
                'source'            => 'MarkGroupCancelledController',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => $result['already_cancelled']
                ? 'Це заняття вже було скасовано. Журнали очищено повторно.'
                : 'Групове заняття скасовано, журнали очищено.',
            'data' => [
                'id'     => $lesson->id,
                'status' => $lesson->status->value,
            ],
            'meta' => [
                'deleted_logs' => $result['deleted_logs'],
            ],
        ], Response::HTTP_OK);
    }
}
