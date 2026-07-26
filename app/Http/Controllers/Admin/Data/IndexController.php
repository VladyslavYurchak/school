<?php

namespace App\Http\Controllers\Admin\Data;

use App\Http\Controllers\Controller;
use App\Models\LessonLog;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentSubscription;
use Carbon\Carbon;
use App\Services\Data\TeacherMonthlyReportService;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request, TeacherMonthlyReportService $svc)
    {
        $validated = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2022,' . (now()->year + 1)],
        ]);

        $selectedMonth = (int) ($validated['month'] ?? now()->month);
        $selectedYear = (int) ($validated['year'] ?? now()->year);

        $monthStart = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth()->toDateString();
        $monthEnd   = Carbon::create($selectedYear, $selectedMonth, 1)->endOfMonth()->toDateString();

        // === Студенти для attendance ===
        $students = Student::query()
            ->with('teacher')
            ->where(function ($query) use ($selectedYear, $selectedMonth) {
                $query->where('is_active', true)
                    ->orWhereHas('lessonLogs', function ($logs) use ($selectedYear, $selectedMonth) {
                        $logs->whereYear('date', $selectedYear)
                            ->whereMonth('date', $selectedMonth)
                            ->whereIn('status', ['completed', 'charged']);
                    });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

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

        $monthlySubscriptions = StudentSubscription::query()
            ->with(['subscriptionTemplate', 'teacher'])
            ->whereIn('student_id', $students->pluck('id'))
            ->where('type', 'subscription')
            ->where('status', 'active')
            ->whereYear('start_date', $selectedYear)
            ->whereMonth('start_date', $selectedMonth)
            ->orderByDesc('id')
            ->get()
            ->keyBy('student_id');

        $report = $svc->build($selectedYear, $selectedMonth);

        $schoolPayments = StudentSubscription::query()
            ->with(['student', 'teacher', 'subscriptionTemplate', 'payment'])
            ->where('status', 'active')
            ->whereYear('start_date', $selectedYear)
            ->whereMonth('start_date', $selectedMonth)
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get();

        $onlineProductPayments = Payment::query()
            ->with('student')
            ->where('status', 'paid')
            ->whereYear('paid_at', $selectedYear)
            ->whereMonth('paid_at', $selectedMonth)
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get()
            ->filter(function (Payment $payment) {
                $payload = is_array($payment->payload) ? $payment->payload : [];

                return isset($payload['course_id']) || isset($payload['lesson_id']);
            })
            ->values();

        return view('admin.data.index', [
            'students'              => $students,
            'totalLessonsCount'     => $totalLessonsCount,
            'monthLessonsCount'     => $monthLessonsCount,
            'monthlySubscriptions'  => $monthlySubscriptions,
            'selectedMonth'         => $selectedMonth,
            'selectedYear'          => $selectedYear,
            'reports'       => $report['rows'],
            'reportTotals'  => $report['totals'],
            'schoolPayments' => $schoolPayments,
            'schoolPaymentsTotal' => (float) $schoolPayments->sum('price'),
            'onlineProductPayments' => $onlineProductPayments,
            'onlineProductPaymentsTotal' => (float) $onlineProductPayments->sum('amount'),
        ]);
    }
}
