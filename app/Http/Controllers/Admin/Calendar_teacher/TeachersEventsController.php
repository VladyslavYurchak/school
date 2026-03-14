<?php

namespace App\Http\Controllers\Admin\Calendar_teacher;

use App\Http\Controllers\Controller;
use App\Models\PlannedLesson;
use Illuminate\Http\Request;

class TeachersEventsController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = PlannedLesson::with(['teacher', 'student', 'group']);

        // Date range from FullCalendar
        if ($request->filled('start') && $request->filled('end')) {
            $query->whereBetween('start_date', [$request->start, $request->end]);
        }

        // Optional teacher filter
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        $lessons = $query->get();



        // Map lessons to FullCalendar events
        $events = $lessons->map(function (PlannedLesson $lesson) {

            // ------------------------------------
            // 🎨 COLOR SYSTEM
            // ------------------------------------
            $status = $lesson->status?->value;

            $color = '#bdbdbd'; // default = planned (grey)
            $textColor = '#000000';

            if ($status === 'completed') {
                $color = '#4caf50';   // green
                $textColor = '#ffffff';
            }

            if ($status === 'cancelled') {
                $color = '#e53935';   // red
                $textColor = '#ffffff';
            }

            // Формування назви події
            $title = match ($lesson->lesson_type?->value) {

                // групове заняття → показати назву групи
                'group' => $lesson->group?->name ?? 'Групове заняття',

                // пара → також група або своя назва
                'pair' => $lesson->group?->name ?? 'Пара',

                // індивідуальні уроки → імʼя студента
                'individual' => $lesson->student?->full_name ?? 'Індивідуальне заняття',

                // пробне → фіксований текст
                'trial' => 'Пробне',

                // якщо тип не визначений
                default => $lesson->title ?? 'Урок',
            };

            return [
                'id'        => $lesson->id,
                'title'     => $title,
                'start'     => optional($lesson->start_date)?->toIso8601String(),
                'end'       => optional($lesson->end_date)?->toIso8601String(),

                // 🎨 COLORS FOR FULLCALENDAR
                'backgroundColor' => $color,
                'borderColor'     => $color,
                'textColor'       => $textColor,

                // Additional info
                'extendedProps' => [
                    'teacher' => optional($lesson->teacher)->full_name,
                    'student' => optional($lesson->student)->full_name,
                    'group'   => optional($lesson->group)->name,
                    'status'  => $status,
                    'type'    => $lesson->lesson_type?->value,
                ],
            ];
        });

        return response()->json($events);
    }
}
