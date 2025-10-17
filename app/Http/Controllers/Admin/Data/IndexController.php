<?php

namespace App\Http\Controllers\Admin\Data;

use App\Http\Controllers\Controller;
use App\Models\LessonLog;
use App\Models\Student;
use App\Models\StudentSubscription;
use Carbon\Carbon;
use App\Services\Data\TeacherMonthlyReportService; // ✅ додаємо
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request, TeacherMonthlyReportService $svc)
    {
        $selectedMonth = (int) ($request->input('month') ?? now()->month);
        $selectedYear  = (int) ($request->input('year')  ?? now()->year);

        $monthStart = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth()->toDateString();
        $monthEnd   = Carbon::create($selectedYear, $selectedMonth, 1)->endOfMonth()->toDateString();

        // === Студенти для attendance ===
        $students = Student::with(['teacher', 'subscriptionTemplate'])->get();

        $lessonLogs = LessonLog::whereIn('student_id', $students->pluck('id'))
            ->whereIn('status', ['completed', 'charged'])
            ->get();

        $totalLessonsCount = [];
        $monthLessonsCount = [];
        foreach ($lessonLogs as $log) {
            $sid  = $log->student_id;
            $date = Carbon::parse($log->date);
            $totalLessonsCount[$sid] = ($totalLessonsCount[$sid] ?? 0) + 1;
            if ($date->betweenIncluded($monthStart, $monthEnd)) {
                $monthLessonsCount[$sid] = ($monthLessonsCount[$sid] ?? 0) + 1;
            }
        }

        $singlePaymentsCount = [];
        foreach ($students as $student) {
            $singlePaymentsCount[$student->id] = StudentSubscription::where('student_id', $student->id)
                ->whereNull('subscription_template_id')
                ->count();
        }

        // Пробні за місяць по студенту
        $trialCountsByStudent = [];
        $trialCostsByStudent  = [];
        LessonLog::query()
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->whereIn('status', ['completed', 'charged'])
            ->where('lesson_type', 'trial')
            ->get()
            ->groupBy('student_id')
            ->each(function ($logs, $sid) use (&$trialCountsByStudent, &$trialCostsByStudent) {
                $trialCountsByStudent[$sid] = $logs->count();
                $trialCostsByStudent[$sid]  = (float) $logs->sum('teacher_payout_amount');
            });

        // ✅ отримуємо готове зведення з сервісу
        $reports = $svc->build($selectedYear, $selectedMonth);

        return view('admin.data.index', [
            'students'              => $students,
            'singlePaymentsCount'   => $singlePaymentsCount,
            'totalLessonsCount'     => $totalLessonsCount,
            'monthLessonsCount'     => $monthLessonsCount,
            'trialCountsByStudent'  => $trialCountsByStudent,
            'trialCostsByStudent'   => $trialCostsByStudent,
            'teachers'              => collect(array_column($reports, 'teacher')), // можна передати окремо
            'selectedMonth'         => $selectedMonth,
            'selectedYear'          => $selectedYear,
            'reports'               => $reports,
        ]);
    }
}
