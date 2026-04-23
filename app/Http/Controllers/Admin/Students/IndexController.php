<?php

namespace App\Http\Controllers\Admin\Students;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;

class IndexController extends Controller
{
    public function __invoke()
    {
        $now = Carbon::now('Europe/Kyiv');
        $currentMonth = $now->format('Y-m');

        $students = Student::with(['teacher', 'subscriptionTemplate', 'user'])->get();
        $teachers = Teacher::all();
        $subscriptionTemplates = SubscriptionTemplate::all();

        $users = User::query()
            ->whereDoesntHave('student')
            ->orderBy('name')
            ->get();

        $subscriptions = StudentSubscription::all()->groupBy('student_id');

        $singlePaymentsCount = StudentSubscription::whereNull('subscription_template_id')
            ->selectRaw('student_id, COUNT(*) as cnt')
            ->groupBy('student_id')
            ->pluck('cnt', 'student_id');

        $paidMonthsByStudent = [];

        foreach ($students as $student) {
            $studentSubs = $subscriptions[$student->id] ?? collect();
            $paidMonthsByStudent[$student->id] = [];

            foreach ($studentSubs as $studentSub) {
                $start = Carbon::parse($studentSub->start_date, 'Europe/Kyiv');
                $month = $start->format('Y-m');

                if (!isset($paidMonthsByStudent[$student->id][$month])) {
                    $paidMonthsByStudent[$student->id][$month] = 0;
                }

                $paidMonthsByStudent[$student->id][$month] += $studentSub->price;
            }
        }

        $activeStudents = $students->where('is_active', true)
            ->sortBy(function ($student) use ($paidMonthsByStudent, $currentMonth) {
                $paidMonths = $paidMonthsByStudent[$student->id] ?? [];
                return array_key_exists($currentMonth, $paidMonths) ? 1 : 0;
            });

        $inactiveStudents = $students->where('is_active', false);

        return view('admin.students.index', compact(
            'activeStudents',
            'inactiveStudents',
            'teachers',
            'subscriptionTemplates',
            'users',
            'paidMonthsByStudent',
            'singlePaymentsCount'
        ));
    }
}
