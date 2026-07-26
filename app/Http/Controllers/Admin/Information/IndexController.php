<?php

namespace App\Http\Controllers\Admin\Information;

use App\Http\Controllers\Controller;
use App\Models\LessonLog;
use App\Models\PlannedLesson;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {

        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'view' => ['nullable', Rule::in(['day', 'week'])],
        ]);

        $date = $validated['date'] ?? now()->toDateString();
        $view = $validated['view'] ?? 'day';

        if ($view === 'week') {
            $startOfWeek = Carbon::parse($date)->startOfWeek(Carbon::MONDAY);
            $endOfWeek   = Carbon::parse($date)->endOfWeek(Carbon::SUNDAY);

            $logs = LessonLog::with(['student','teacher','group'])
                ->whereBetween('date', [$startOfWeek, $endOfWeek])
                ->whereIn('status', ['completed', 'charged'])
                ->orderBy('date')
                ->orderBy('time')
                ->get();

            $rescheduledLessons = PlannedLesson::withTrashed()
                ->with(['student','teacher','group'])
                ->whereBetween('start_date', [$startOfWeek, $endOfWeek])
                ->where('status', 'rescheduled')
                ->orderBy('start_date')
                ->get();


            return view('admin.information.index', compact('logs','rescheduledLessons','date','view','startOfWeek','endOfWeek'));
        }

        // день
        $logs = LessonLog::with(['student', 'teacher', 'group'])
            ->whereDate('date', $date)
            ->whereIn('status', ['completed', 'charged'])
            ->orderBy('time')
            ->get();


        $rescheduledLessons = PlannedLesson::withTrashed() // 🔑 тут
        ->with(['student','teacher','group'])
            ->whereDate('start_date', $date) // або whereBetween для тижня
            ->where('status', 'rescheduled')
            ->orderBy('start_date')
            ->get();

        return view('admin.information.index', compact('logs','rescheduledLessons','date','view'));
    }
}
