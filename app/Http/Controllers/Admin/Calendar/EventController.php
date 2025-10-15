<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\PlannedLesson;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EventController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = auth()->user();

        // Визначаємо вчителя (user->teacher або admin->teacher)
        $teacher = $user->teacher ?? $user->admin?->teacher ?? null;
        if (!$teacher) {
            return response()->json([], 200);
        }

        // Діапазон дат (із FullCalendar) або дефолт ±60 днів
        $start = $request->input('start');
        $end   = $request->input('end');

        $rangeStart = $start ? Carbon::parse($start) : now()->subDays(60);
        $rangeEnd   = $end   ? Carbon::parse($end)   : now()->addDays(60);

        // Якщо кінець не пізніше за початок — розширюємо на +1 день
        if ($rangeEnd->lessThanOrEqualTo($rangeStart)) {
            $rangeEnd = (clone $rangeStart)->addDay();
        }

        // Вибірка уроків: перетин інтервалів
        $lessons = PlannedLesson::with([
            'student:id,first_name,last_name',
            'group',
            'group.students:id,first_name,last_name',
        ])
            ->where('teacher_id', $teacher->id)
            ->where('start_date', '<', $rangeEnd)
            ->whereRaw('COALESCE(end_date, start_date) >= ?', [$rangeStart])
            ->orderBy('start_date')
            ->orderBy('end_date')
            ->get()
            ->filter(fn ($l) => !empty($l->start_date))
            ->values();

        // Мапінг у події FullCalendar
        $events = $lessons->map(function ($lesson) {
            $studentLabel = $lesson->student
                ? trim(($lesson->student->last_name ?? '') . ' ' . ($lesson->student->first_name ?? ''))
                : '';

            $group      = $lesson->group;
            $groupLabel = $group->name ?? $group->title ?? '';
            $baseTitle  = $studentLabel ?: ($groupLabel ?: ($lesson->title ?? 'Заняття'));

            $title = match ($lesson->lesson_type) {
                LessonType::Trial->value => 'Пробне: ' . $baseTitle,
                LessonType::Group->value => 'Група: '  . ($groupLabel ?: $baseTitle),
                LessonType::Pair->value  => 'Пара: '   . ($groupLabel ?: $baseTitle),
                default => $baseTitle,
            };

            $bg = match ($lesson->status) {
                LessonStatus::Completed->value   => '#198754',
                LessonStatus::Rescheduled->value => '#ffc107',
                LessonStatus::Cancelled->value   => '#dc3545',
                LessonStatus::Planned->value     => '#6c757d',
                default       => '#0d6efd',
            };

            return [
                'id'              => $lesson->id,
                'title'           => $title,
                // Завдяки casts у моделі — це Carbon і віддаємо ISO-рядок
                'start'           => $lesson->start_date?->toIso8601String(),
                'end'             => $lesson->end_date?->toIso8601String(),
                'allDay'          => false,
                'backgroundColor' => $bg,
                'extendedProps'   => [
                    'lesson_id'   => $lesson->id,
                    'lesson_type' => $lesson->lesson_type,
                    'status'      => $lesson->status,
                    'group_id'    => $lesson->group_id,
                    'members'     => $group?->students
                        ? $group->students->map(fn($s) => [
                            'id'   => $s->id,
                            'name' => trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')),
                        ])->values()
                        : [],
                ],
            ];
        })->values();

        return response()->json($events);
    }
}
