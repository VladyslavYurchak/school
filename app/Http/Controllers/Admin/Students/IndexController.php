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

        // Отримуємо всіх студентів з потрібними зв’язками
        $students = Student::with(['teacher', 'subscriptionTemplate'])->get();
        $teachers = Teacher::all();

        $subscriptionTemplates = SubscriptionTemplate::all();
        $paidMonthsByStudent = [];
        $singlePaymentsCount = [];


        foreach ($students as $student) {
            $subscriptions = StudentSubscription::where('student_id', $student->id)->get();

            $paidMonthsByStudent[$student->id] = [];

            foreach ($subscriptions as $subscription) {
                // Парсимо дату підписки з TZ Київ
                $start = Carbon::parse($subscription->start_date, 'Europe/Kyiv');
                $month = $start->format('Y-m');
                $paidMonthsByStudent[$student->id][$month] = $subscription->price;
            }

            $singlePaymentsCount[$student->id] = StudentSubscription::where('student_id', $student->id)
                ->whereNull('subscription_template_id')
                ->count();
        }


        // Розбиваємо студентів на активних та неактивних
        $activeStudents = $students->where('is_active', true)
            ->sortBy(function ($student) use ($paidMonthsByStudent, $currentMonth) {
                $paidMonths = $paidMonthsByStudent[$student->id] ?? [];
                // Якщо оплатив поточний місяць — 1, інакше 0
                return array_key_exists($currentMonth, $paidMonths) ? 1 : 0;
            });


        $inactiveStudents = $students->where('is_active', false);

        return view('admin.students.index', compact(
            'activeStudents',
            'inactiveStudents',
            'teachers',
            'subscriptionTemplates',
            'paidMonthsByStudent',
            'singlePaymentsCount'  // додаємо сюди
        ));
    }
}
