<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Http\Controllers\Controller;
use App\Models\PlannedLesson;
use App\Models\LessonLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class CompleteLessonController extends Controller
{
    public function __invoke($id)
    {

        /* закоментовано бо нк використовуэться певно

         * /DB::transaction(function () use ($id) {
            $lesson = PlannedLesson::findOrFail($id);

            // Оновлюємо статус
            $lesson->status = 'completed';
            $lesson->save();

            // Створюємо запис у журнал
            LessonLog::create([
                'student_id' => $lesson->student_id,
                'teacher_id' => $lesson->teacher_id,
                'date' => date('Y-m-d', strtotime($lesson->start_date)),
                'time' => date('H:i:s', strtotime($lesson->start_date)),
                'duration' => $lesson->duration ?? 60,
                'status' => 'completed',
                'notes' => $lesson->notes,
            ]);
        });
         */

        return response()->json(['success' => true]);
    }
}
