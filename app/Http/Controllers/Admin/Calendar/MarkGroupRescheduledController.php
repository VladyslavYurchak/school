<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Calendar\MarkGroupRescheduledRequest;
use App\Services\Calendar\RescheduleGroupLessonService;
use Illuminate\Http\JsonResponse;

final class MarkGroupRescheduledController extends Controller
{
    public function __invoke(
        MarkGroupRescheduledRequest $request,
        RescheduleGroupLessonService $service
    ): JsonResponse {
        try {
            $result = $service->handle($request->validated());

            return response()->json(
                [
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'meta'    => $result['meta'] ?? null,
                ],
                $result['status']
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Помилка при перенесенні групового заняття.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
