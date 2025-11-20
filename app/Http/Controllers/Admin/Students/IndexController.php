<?php

namespace App\Http\Controllers\Admin\Students;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use Carbon\Carbon;

class IndexController extends Controller
{
    public function __invoke()
    {
        $now = Carbon::now('Europe/Kyiv');
        $currentMonth = $now->format('Y-m');

        // 1️⃣ Отримуємо всі дані одним махом
        $students = Student::with(['teacher', 'subscriptionTemplate'])->get();
        $teachers = Teacher::all();
        $subscriptionTemplates = SubscriptionTemplate::all();

        // 2️⃣ Підписки групуємо по студенту
        $subscriptions = StudentSubscription::all()->groupBy('student_id');

        // 3️⃣ Підрахунок поразових оплат — одним запитом
        $singlePaymentsCount = StudentSubscription::whereNull('subscription_template_id')
            ->selectRaw('student_id, COUNT(*) as cnt')
            ->groupBy('student_id')
            ->pluck('cnt', 'student_id');

        // 4️⃣ Формуємо масиви оплат по місяцях
        $paidMonthsByStudent = [];

        foreach ($students as $student) {
            $studentSubs = $subscriptions[$student->id] ?? collect();
            $paidMonthsByStudent[$student->id] = [];

            foreach ($studentSubs as $studentSub) {
                $start = Carbon::parse($studentSub->start_date, 'Europe/Kyiv');
                $month = $start->format('Y-m');

                // додаємо всі оплати за місяць
                if (!isset($paidMonthsByStudent[$student->id][$month])) {
                    $paidMonthsByStudent[$student->id][$month] = 0;
                }

                $paidMonthsByStudent[$student->id][$month] += $studentSub->price;
            }
        }

        // 5️⃣ Поділ студентів на активних і неактивних
        $activeStudents = $students->where('is_active', true)
            ->sortBy(function ($student) use ($paidMonthsByStudent, $currentMonth) {
                $paidMonths = $paidMonthsByStudent[$student->id] ?? [];
                return array_key_exists($currentMonth, $paidMonths) ? 1 : 0;
            });

        $inactiveStudents = $students->where('is_active', false);

        // 6️⃣ Повертаємо у view
        return view('admin.students.index', compact(
            'activeStudents',
            'inactiveStudents',
            'teachers',
            'subscriptionTemplates',
            'paidMonthsByStudent',
            'singlePaymentsCount'
        ));
    }
}
