<?php

namespace App\Http\Controllers\Admin\History;

use App\Http\Controllers\Controller;
use App\Models\LessonAction;
use App\Models\Teacher;
use Illuminate\Http\Request;

final class HistoryActionsController extends Controller
{
    public function __invoke(Request $request)
    {
        $teacherId = $request->get('teacher_id');

        $teachers = Teacher::orderBy('last_name')->get(['id', 'first_name', 'last_name']);

        $logs = LessonAction::query()
            ->with(['lesson', 'user', 'lesson.teacher'])
            ->when($teacherId, function ($q) use ($teacherId) {
                $q->whereHas('lesson', function ($q2) use ($teacherId) {
                    $q2->where('teacher_id', $teacherId);
                });
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.history.index', compact('logs', 'teachers', 'teacherId'));
    }
}
