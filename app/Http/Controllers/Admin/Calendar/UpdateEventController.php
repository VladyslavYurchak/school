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

        $start = Carbon::parse($data['date'] . ' ' . $data['time']);
        $end = (clone $start)->addMinutes($data['duration'] ?? 60);

        $lesson = PlannedLesson::findOrFail($id);
        $lesson->update([
            'title'       => $data['title'],
            'start_date'  => $start,
            'end_date'    => $end,
            'student_id'  => $data['student_id'] ?? null,
            'group_id'    => $data['group_id'] ?? null,
            'notes'       => $data['notes'] ?? null,
            'lesson_type' => $data['lesson_type'],
        ]);

        return response()->json(['success' => true]);
    }
}
