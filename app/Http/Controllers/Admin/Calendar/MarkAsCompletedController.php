<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Enums\LessonStatus;
use App\Enums\LessonLogStatus;
use App\Http\Controllers\Controller;
use App\Models\PlannedLesson;
use App\Models\LessonLog;
use App\Services\LessonActionLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MarkAsCompletedController extends Controller
{
    public function __invoke($id)
    {
        try {
            $lesson = PlannedLesson::with('teacher')->findOrFail($id);

            // індивідуальні/пробні тільки тут (group -> інший контролер)
            if (!is_null($lesson->group_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Цей ендпойнт для індивідуальних/пробних занять. Для груп і пар — використай груповий контролер.'
                ], 422);
            }

            if ($lesson->status === LessonStatus::Completed->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'Дане заняття вже відмічено як проведене.'
                ]);
            }

            DB::transaction(function () use ($lesson) {
                $lessonStart = $lesson->start_date;
                $lesson->status = LessonStatus::Completed->value;
                $lesson->save();

                $start    = Carbon::parse($lesson->start_date);
                $date     = $start->toDateString();
                $time     = $start->format('H:i:s');
                $duration = $lesson->duration ?? max(15, $start->diffInMinutes(Carbon::parse($lesson->end_date)) ?: 60);

                $teacher = $lesson->teacher;
                $type = $lesson->lesson_type;

                // Вибір ставки згідно типу
                $basis    = 'per_lesson';
                $baseRate = 0.0;

                if ($type === \App\Enums\LessonType::Trial && !is_null($teacher->trial_lesson_price)) {
                    $baseRate = (float) $teacher->trial_lesson_price;
                } else {
                    $baseRate = (float) ($teacher->lesson_price ?? 0);
                }

                // Фактична виплата за урок (індивідуальний/пробний — вся сума)
                $amount = round($baseRate, 2);



                LessonLog::updateOrCreate(
                    [
                        'lesson_id' => $lesson->id, // 1:1 з планом
                    ],
                    [
                        'student_id'  => $lesson->student_id,   // може бути null для trial
                        'teacher_id'  => $lesson->teacher_id,
                        'group_id'    => $lesson->group_id,     // має бути null
                        'lesson_type' => $type,
                        'date'        => $date,
                        'time'        => $time,
                        'duration'    => $duration,
                        'status'      => LessonLogStatus::Completed->value,
                        'notes'       => $lesson->notes,

                        // snapshot оплати
                        'teacher_rate_amount_at_charge' => $baseRate, // ставка за заняття
                        'teacher_payout_basis'          => $basis,    // per_lesson
                        'teacher_payout_amount'         => $amount,   // вся сума
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

            });

            return response()->json([
                'success' => true,
                'message' => 'Заняття успішно відмічено як проведене.'
            ]);

        } catch (\Exception $e) {
            Log::error('MarkAsCompletedController error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Помилка при оновленні статусу.'
            ], 500);
        }
    }
}
