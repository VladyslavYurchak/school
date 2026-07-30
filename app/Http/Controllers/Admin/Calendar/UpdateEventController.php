<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Calendar\UpdateEventRequest;
use App\Models\PlannedLesson;
use App\Models\Teacher;
use App\Services\Calendar\CalendarAvailabilityService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateEventController extends Controller
{
    public function __invoke(
        $id,
        UpdateEventRequest $request,
        CalendarAvailabilityService $availability
    )
    {
        $data = $request->validated();

        $teacher = auth()->user()->teacher;
        if (!$teacher) {
            abort(403, 'Доступ заборонено: ви не викладач');
        }
        $start = Carbon::parse($data['date'] . ' ' . $data['time']);
        $end = (clone $start)->addMinutes($data['duration'] ?? 60);


        DB::transaction(function () use ($availability, $end, $id, $start, $teacher): void {
            Teacher::query()->lockForUpdate()->findOrFail($teacher->id);

            $lesson = PlannedLesson::query()
                ->whereKey($id)
                ->where('teacher_id', $teacher->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($availability->teacherHasOverlap($teacher->id, $start, $end, $lesson->id)) {
                throw ValidationException::withMessages([
                    'date' => 'Викладач уже має інше заняття в цей час.',
                ]);
            }

            $lesson->update([
                'start_date' => $start,
                'end_date' => $end,
            ]);
        });

        return response()->json(['success' => true]);
    }
}
