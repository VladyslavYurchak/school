<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Calendar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Calendar\EventIndexRequest;
use App\Http\Resources\CalendarEventResource;
use App\Models\PlannedLesson;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class EventController extends Controller
{
    public function __invoke(EventIndexRequest $request): AnonymousResourceCollection
    {
        $user = $request->user();

        // Визначаємо вчителя (user->teacher або admin->teacher)
        $teacher = $user->teacher ?? $user->admin?->teacher ?? null;
        if (!$teacher) {
            return CalendarEventResource::collection(collect());
        }
;

        [$rangeStart, $rangeEnd] = $request->range();

        $lessons = PlannedLesson::query()
            ->select(['id','teacher_id','student_id','group_id','title','lesson_type','status','start_date','end_date'])
            ->with([
                'student:id,first_name,last_name',
                'group:id,name',                // залишаємо тільки наявні
                'group.students:id,first_name,last_name',
            ])
            ->where('teacher_id', $teacher->id)
            ->whereNotNull('start_date')
            ->intersects($rangeStart, $rangeEnd)
            ->orderBy('start_date')
            ->orderByRaw('COALESCE(end_date, start_date)')
            ->get();


        return CalendarEventResource::collection($lessons);
    }
}
