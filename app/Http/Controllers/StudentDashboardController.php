<?php

namespace App\Http\Controllers;

use App\Enums\LessonStatus;
use App\Models\PlannedLesson;
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

        if (! $student) {
            return view('student.pending-profile', [
                'user' => $user,
            ]);
        }

        $upcomingLessons = PlannedLesson::query()
            ->with(['teacher', 'group'])
            ->where('status', LessonStatus::Planned->value)
            ->where('start_date', '>=', now())
            ->where(function ($query) use ($student) {
                $query->where('student_id', $student->id);

                if ($student->group_id) {
                    $query->orWhere('group_id', $student->group_id);
                }
            })
            ->orderBy('start_date')
            ->limit(20)
            ->get();

        return view('student.dashboard', [
            'student' => $student,
            'teacher' => $student->teacher,
            'subscription' => $student->activeSubscription,
            'payments' => $student->payments,
            'lessonLogs' => $student->lessonLogs,
            'upcomingLessons' => $upcomingLessons,
            'telegramAccount' => $user->telegramAccount()->first(),
            'courses' => $user->courses()->with('language')->wherePivot('status', 'paid')->get(),
            'lessons' => $user->lessons()
                ->with('course.language')
                ->wherePivot('status', 'paid')
                ->where('lessons.is_published', true)
                ->get(),
        ]);
    }
}
