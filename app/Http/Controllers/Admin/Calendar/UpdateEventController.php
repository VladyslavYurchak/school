<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Calendar\UpdateEventRequest;
use App\Models\PlannedLesson;
use Carbon\Carbon;

class UpdateEventController extends Controller
{
    public function __invoke($id, UpdateEventRequest $request)
    {
        $data = $request->validated();

        $teacher = auth()->user()->teacher;
        if (!$teacher) {
            abort(403, 'Доступ заборонено: ви не викладач');
        }
        $start = Carbon::parse($data['date'] . ' ' . $data['time']);
        $end = (clone $start)->addMinutes($data['duration'] ?? 60);


        $lesson = PlannedLesson::where('id', $id)
            ->where('teacher_id', $teacher->id)
            ->firstOrFail();

        $lesson->update([
            'start_date'  => $start,
            'end_date'    => $end,
        ]);

        return response()->json(['success' => true]);
    }
}
