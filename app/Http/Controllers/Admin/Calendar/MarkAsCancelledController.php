<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Enums\LessonStatus;
use App\Http\Controllers\Controller;
use App\Models\PlannedLesson;
use App\Models\LessonLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MarkAsCancelledController extends Controller
{
    public function __invoke($id)
    {
        try {
            $lesson = PlannedLesson::findOrFail($id);

            // якщо це групове — користуй груповий ендпойнт


            // вже скасований? — робимо no-op
            if ($lesson->status === LessonStatus::Cancelled->value) {
                return response()->json([
                    'success' => true,
                    'message' => 'Заняття вже було скасоване.'
                ]);
            }


            DB::transaction(function () use ($lesson) {
                // позначаємо як скасоване
                $lesson->status = LessonStatus::Cancelled->value;
                $lesson->save();

                // обчислюємо ключ журналу один раз
                $start = Carbon::parse($lesson->start_date);
                $date  = $start->toDateString();
                $time  = $start->format('H:i:s');

                // видаляємо лише відповідний запис журналу
                LessonLog::where('student_id', $lesson->student_id)   // може бути null (trial) — Laravel поставить IS NULL
                ->where('teacher_id', $lesson->teacher_id)
                    ->where('group_id', $lesson->group_id)             // зазвичай null для індивідуалок
                    ->whereDate('date', $date)
                    ->whereTime('time', $time)
                    ->delete();

                // soft delete самого уроку
                $lesson->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Заняття скасоване та видалене з журналу.'
            ]);

        } catch (\Exception $e) {
            Log::error('MarkAsCancelledController error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Помилка при скасуванні заняття.'
            ], 500);
        }
    }
}
