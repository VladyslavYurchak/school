<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Enums\LessonLogStatus;
use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Http\Controllers\Controller;
use App\Models\PlannedLesson;
use App\Models\LessonLog;
use App\Services\LessonActionLogger;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class MarkAsCompletedController extends Controller
{
    public function __invoke($id)
    {
        try {
            $result = DB::transaction(function () use ($id) {
                $query = PlannedLesson::with('teacher')
                    ->whereKey((int) $id);

                $user = auth()->user();

                if ($user->role === 'teacher') {
                    $teacherId = optional($user->teacher)->id;

                    if (!$teacherId) {
                        abort(403);
                    }

                    $query->where('teacher_id', $teacherId);
                }

                $lesson = $query
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!is_null($lesson->group_id)) {
                    return [
                        'status' => 422,
                        'success' => false,
                        'message' => 'Цей ендпойнт для індивідуальних/пробних занять. Для груп і пар — використай груповий контролер.',
                    ];
                }

                if ($lesson->status === LessonStatus::Completed) {
                    return [
                        'status' => 200,
                        'success' => false,
                        'message' => 'Дане заняття вже відмічено як проведене.',
                    ];
                }

                $lessonStart = $lesson->start_date;

                $lesson->status = LessonStatus::Completed;
                $lesson->save();

                $start    = Carbon::parse($lesson->start_date);
                $date     = $start->toDateString();
                $time     = $start->format('H:i:s');
                $duration = $lesson->duration ?? max(15, $start->diffInMinutes(Carbon::parse($lesson->end_date)) ?: 60);

                $teacher = $lesson->teacher;
                $type = $lesson->lesson_type;

                $basis = 'per_lesson';

                if ($type === LessonType::Trial && !is_null($teacher?->trial_lesson_price)) {
                    $baseRate = (float) $teacher->trial_lesson_price;
                } else {
                    $baseRate = (float) ($teacher?->lesson_price ?? 0);
                }

                $amount = round($baseRate, 2);

                LessonLog::updateOrCreate(
                    [
                        'lesson_id' => $lesson->id,
                    ],
                    [
                        'student_id'  => $lesson->student_id,
                        'teacher_id'  => $lesson->teacher_id,
                        'group_id'    => $lesson->group_id,
                        'lesson_type' => $type,
                        'date'        => $date,
                        'time'        => $time,
                        'duration'    => $duration,
                        'status'      => LessonLogStatus::Completed,
                        'notes'       => $lesson->notes,

                        'teacher_rate_amount_at_charge' => $baseRate,
                        'teacher_payout_basis'          => $basis,
                        'teacher_payout_amount'         => $amount,
                        'charged_at'                    => now(),
                    ]
                );

                LessonActionLogger::log(
                    lessonId: $lesson->id,
                    action: 'completed',
                    lessonDatetime: $lessonStart,
                    newLessonDatetime: null,
                    meta: [
                        'source' => 'MarkAsCompletedController',
                    ]
                );

                return [
                    'status' => 200,
                    'success' => true,
                    'message' => 'Заняття успішно відмічено як проведене.',
                ];
            });

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
            ], $result['status']);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lesson with this ID was not found.',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Exception $e) {
            Log::error('MarkAsCompletedController error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'lesson_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Помилка при оновленні статусу.',
            ], 500);
        }
    }
}
