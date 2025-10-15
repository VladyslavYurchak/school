<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Enums\LessonLogStatus;
use App\Enums\LessonStatus;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonLog;
use App\Models\Group;
use App\Models\PlannedLesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MarkGroupAttendanceController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:groups,id',
            'lesson_id' => 'required|exists:planned_lessons,id',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'present_students' => 'array',
            'present_students.*' => 'exists:students,id',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $group  = Group::with('students')->findOrFail($request->group_id);
                $lesson = PlannedLesson::findOrFail($request->lesson_id);
                $teacherId = $group->teacher_id ?? optional(auth()->user()->teacher)->id;

                if ($lesson->status !== LessonStatus::Completed->value) {
                    $lesson->status = LessonStatus::Completed->value;
                    $lesson->save();
                }

                // 👇 нормалізуй масив присутніх до int
                $present = array_map('intval', $request->present_students ?? []);

                foreach ($group->students as $student) {
                    $studentId = (int) $student->id;

                    $status = in_array($studentId, $present, true)
                        ? LessonLogStatus::Completed->value
                        : LessonLogStatus::Charged->value;

                    $exists = LessonLog::where('student_id', $studentId)
                        ->where('group_id', $group->id)
                        ->whereDate('date', $request->date)
                        ->whereTime('time', $request->time)
                        ->exists();

                    if (!$exists) {
                        LessonLog::create([
                            'student_id'  => $studentId,
                            'teacher_id'  => $teacherId,
                            'lesson_type' => $lesson->lesson_type,
                            'group_id'    => $group->id,
                            'date'        => $request->date,
                            'time'        => $request->time,
                            'duration'    => $lesson->duration ?? 60,
                            'status'      => $status,
                        ]);
                    }
                }
            });


            return response()->json(['success' => true, 'message' => 'Відвідуваність збережена']);
        } catch (\Exception $e) {
            Log::error('MarkGroupAttendance error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Помилка при збереженні'], 500);
        }
    }
}
