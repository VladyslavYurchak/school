<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        abort_unless($user && $user->isStudent(), 403);

        $student = $user->student()
            ->with([
                'teacher.user',
                'activeSubscription.subscriptionTemplate',
                'payments' => fn ($q) => $q->latest(),
                'lessonLogs' => fn ($q) => $q->latest('date')->limit(20),
            ])
            ->first();

        abort_if(!$student, 404, 'Студента не знайдено.');

        return view('student.dashboard', [
            'student' => $student,
            'teacher' => $student->teacher,
            'subscription' => $student->activeSubscription,
            'payments' => $student->payments,
            'lessonLogs' => $student->lessonLogs,
        ]);
    }
}
