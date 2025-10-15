<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Enums\LessonStatus;
use App\Enums\LessonLogStatus;
use App\Http\Controllers\Controller;
use App\Models\PlannedLesson;
use App\Models\LessonLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MarkAsCompletedController extends Controller
{
    public function __invoke($id)
    {
        try {
            $lesson = PlannedLesson::findOrFail($id);

            // якщо це група — користуйся груповим ендпойнтом
            if (!is_null($lesson->group_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Цей ендпойнт призначений для індивідуальних/пробних занять. Для груп — використай груповий контролер відвідуваності.'
                ], 422);
            }

            if ($lesson->status === LessonStatus::Completed->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'Дане заняття вже відмічено як проведене.'
                ]);
            }

            DB::transaction(function () use ($lesson) {
                $lesson->status = LessonStatus::Completed->value;
                $lesson->save();

                $start = Carbon::parse($lesson->start_date);
                $date  = $start->toDateString();
                $time  = $start->format('H:i:s');
                $duration = $lesson->duration ?? max(15, $start->diffInMinutes(Carbon::parse($lesson->end_date)) ?: 60);

                // Уникаємо дублікатів: one-log-per-student-teacher-datetime
                LessonLog::updateOrCreate(
                    [
                        'student_id' => $lesson->student_id,     // може бути null для trial
                        'teacher_id' => $lesson->teacher_id,
                        'group_id'   => $lesson->group_id,       // зазвичай null
                        'date'       => $date,
                        'time'       => $time,
                    ],
                    [
                        'lesson_type' => $lesson->lesson_type,   // напр. 'trial'
                        'duration'    => $duration,
                        'status'      => LessonLogStatus::Completed->value, // ✅ правильний enum
                        'notes'       => $lesson->notes,
                    ]
                );
            });

            return response()->json([
                'success' => true,
                'message' => 'Заняття успішно відмічено як проведене.'
            ]);

        } catch (\Exception $e) {
            Log::error('MarkAsCompletedController error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Помилка при оновленні статусу.'
            ], 500);
        }
    }
}
