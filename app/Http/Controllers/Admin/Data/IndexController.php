<?php

namespace App\Http\Controllers\Admin\Data;

use App\Http\Controllers\Controller;
use App\Models\LessonLog;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\Teacher;
use Carbon\Carbon;

class IndexController extends Controller
{
    public function __invoke()
    {
        $selectedMonth = request('month') ?? now()->month;
        $selectedYear = request('year') ?? now()->year;

        $students = Student::with(['teacher', 'subscriptionTemplate'])->get();

        // Всі lessonLogs для студентів
        $lessonLogs = LessonLog::whereIn('student_id', $students->pluck('id'))
            ->whereIn('status', ['completed', 'charged'])
            ->get();

        // Кількість усіх занять та занять за місяць
        $totalLessonsCount = [];
        $monthLessonsCount = [];

        foreach ($lessonLogs as $log) {
            $studentId = $log->student_id;
            $date = Carbon::parse($log->date);

            $totalLessonsCount[$studentId] = ($totalLessonsCount[$studentId] ?? 0) + 1;

            if ($date->year == $selectedYear && $date->month == $selectedMonth) {
                $monthLessonsCount[$studentId] = ($monthLessonsCount[$studentId] ?? 0) + 1;
            }
        }

        // Кількість одноразових оплат
        $singlePaymentsCount = [];
        foreach ($students as $student) {
            $singlePaymentsCount[$student->id] = StudentSubscription::where('student_id', $student->id)
                ->whereNull('subscription_template_id')
                ->count();
        }

        $teachers = Teacher::with('lessonLogs')->get();

        // Підрахунок зарплат і доходу школи через методи моделі
        $incomeData = [];
        foreach ($teachers as $teacher) {
            // Витягуємо кількість індивідуальних і групових занять
            $counts = $teacher->getMonthLessonCounts($selectedYear, $selectedMonth);

            // Зберігаємо їх для відображення
            $teacher->individualCount = $counts['individual'];
            $teacher->groupCount = $counts['group'];

            // Розраховуємо зарплату
            $teacher->salary = $teacher->getMonthSalary($selectedYear, $selectedMonth);

            // Розраховуємо загальний дохід (метод already returns all needed data)
            $income = $teacher->getMonthlyIncome($selectedYear, $selectedMonth);

            $incomeData[$teacher->id] = [
                'individualIncome' => $income['individual_subscriptions_sum'],
                'individualCosts' => $income['individual_lessons_count'] * ($teacher->lesson_price ?? 0),
                'individualProfit' => $income['individual_income'],
                'groupIncome' => $income['group_subscriptions_sum'],
                'groupCosts' => $income['group_lessons_count'] * ($teacher->group_lesson_price ?? 0),
                'groupProfit' => $income['group_income'],
                'totalProfit' => $income['total_income'],
            ];

        }

        return view('admin.data.index', compact(
            'students',
            'singlePaymentsCount',
            'totalLessonsCount',
            'monthLessonsCount',
            'teachers',
            'selectedMonth',
            'selectedYear',
            'incomeData'
        ));
    }
}
