<?php

namespace App\Http\Controllers\Admin\Teacher_income;

use App\Http\Controllers\Controller;
use App\Models\LessonLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $teacher = Auth::user()->teacher;

        $selectedMonth = $request->input('month', now()->month);
        $selectedYear = $request->input('year', now()->year);

        $lessonLogs = LessonLog::with(['student', 'group'])
            ->where('teacher_id', $teacher->id)
            ->whereIn('status', ['completed', 'charged'])
            ->whereYear('date', $selectedYear)
            ->whereMonth('date', $selectedMonth)
            ->get();

        // Індивідуальні заняття
        $individualLessons = [];
        foreach ($lessonLogs->whereNull('group_id') as $log) {
            $studentName = $log->student->full_name ?? '—';
            if (!isset($individualLessons[$studentName])) {
                $individualLessons[$studentName] = 0;
            }
            $individualLessons[$studentName]++;
        }

        // Групові заняття (групуємо за унікальним заняттям)
        $groupLessons = [];
        $groupLogs = $lessonLogs->whereNotNull('group_id');

        $uniqueGroupLessons = $groupLogs->groupBy(function ($log) {
            return $log->group_id . '|' . $log->date . '|' . Carbon::parse($log->time)->format('H:i');
        });

        foreach ($uniqueGroupLessons as $groupKey => $logs) {
            $log = $logs->first();
            $groupId = $log->group_id;
            $groupName = $log->group->name ?? 'Група #' . $groupId;

            if (!isset($groupLessons[$groupKey])) {
                $groupLessons[$groupKey] = [
                    'group_id' => $groupId,
                    'name' => $groupName,
                    'count' => 0,
                ];
            }

            $groupLessons[$groupKey]['count']++;
        }

        // Формуємо $data для view
        $data = [];

        foreach ($individualLessons as $studentName => $count) {
            $data[$studentName] = [
                'student' => (object)['full_name' => $studentName],
                'individualCount' => $count,
                'groupCount' => 0,
                'individualEarned' => $count * ($teacher->lesson_price ?? 0),
                'groupEarned' => 0,
                'totalEarned' => $count * ($teacher->lesson_price ?? 0),
            ];
        }

        foreach ($groupLessons as $groupLesson) {
            $groupName = $groupLesson['name'];
            $count = $groupLesson['count'];

            if (!isset($data[$groupName])) {
                $data[$groupName] = [
                    'student' => (object)['full_name' => $groupName],
                    'individualCount' => 0,
                    'groupCount' => 0,
                    'individualEarned' => 0,
                    'groupEarned' => 0,
                    'totalEarned' => 0,
                ];
            }

            $data[$groupName]['groupCount'] += $count;
            $data[$groupName]['groupEarned'] += $count * ($teacher->group_lesson_price ?? 0);
            $data[$groupName]['totalEarned'] += $count * ($teacher->group_lesson_price ?? 0);
        }

        return view('admin.teacher_income.index', [
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'data' => $data,
        ]);
    }
}
