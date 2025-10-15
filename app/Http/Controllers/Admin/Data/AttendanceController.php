<?php

namespace App\Http\Controllers\Admin\Data;

use App\Http\Controllers\Controller;
use App\Models\LessonLog;

class AttendanceController extends Controller
{
    public function __invoke($studentId)
    {

        $logs = LessonLog::where('student_id', $studentId)
            ->whereIn('status', ['completed', 'charged'])
            ->get()
            ->map(function ($log) {
                return [
                    'title' => $log->status === 'charged' ? '❌ Пропуск' : '✅ Присутній',
                    'start' => $log->date,
                ];
            });

        return response()->json($logs);
    }
}
