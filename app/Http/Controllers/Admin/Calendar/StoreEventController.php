<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Enums\LessonStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Calendar\StoreEventRequest;
use App\Models\PlannedLesson;
use App\Models\SubscriptionTemplate;
use App\Services\LessonActionLogger;
use Carbon\Carbon;

class StoreEventController extends Controller
{
    public function __invoke(StoreEventRequest $request)
    {
        $validated = $request->validated();

        $teacher = auth()->user()->teacher;
        if (!$teacher) {
            abort(403, 'Доступ заборонено: ви не викладач');
        }

        $teacherId = $teacher->id;
        $start     = Carbon::parse($validated['start']);
        $duration  = (int) ($validated['duration'] ?? 60);

        // ✅ Перевірка, що тип абонементу = типу уроку (якщо абонемент передано)
        $template = null;
        if (!empty($validated['subscription_template_id'])) {
            $template = SubscriptionTemplate::findOrFail($validated['subscription_template_id']);
            if ($template->type !== $validated['lesson_type']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Тип абонементу має відповідати типу заняття, яке створюється.',
                ], 422);
            }
        }

        // ==========================
        // Повторювані заняття
        // ==========================
        if (!empty($validated['repeat_weekly'])) {
            $endOfMonth  = $start->copy()->endOfMonth();
            $currentDate = $start->copy();
            $lessons     = [];

            while ($currentDate->lessThanOrEqualTo($endOfMonth)) {
                $lesson = PlannedLesson::create([
                    'title'       => match ($validated['lesson_type']) {
                        'group' => 'Групове заняття',
                        'pair'  => 'Парне заняття',
                        'trial' => 'Пробне заняття',
                        default => 'Індивідуальне заняття',
                    },
                    'start_date'  => $currentDate->format('Y-m-d H:i:s'),
                    'end_date'    => $currentDate->copy()->addMinutes($duration)->format('Y-m-d H:i:s'),
                    'teacher_id'  => $teacherId,
                    'student_id'  => $validated['student_id'] ?? null,
                    'group_id'    => $validated['group_id'] ?? null,
                    'notes'       => $validated['notes'] ?? null,
                    'status'      => LessonStatus::Planned->value,
                    'lesson_type' => $validated['lesson_type'],
                    // 'subscription_template_id' => $template?->id,
                ]);

                // 🔹 Лог: створено урок
                LessonActionLogger::log(
                    lessonId: $lesson->id,
                    action: 'created',
                    lessonDatetime: $lesson->start_date,
                    newLessonDatetime: null,
                    meta: [
                        'repeat_weekly' => true,
                        'source'        => 'StoreEventController',
                    ]
                );

                $lessons[] = $lesson;
                $currentDate->addWeek();
            }

            return response()->json([
                'success' => true,
                'message' => 'Заняття створені до кінця місяця',
                'events'  => collect($lessons)->map(fn($lesson) => [
                    'id'    => $lesson->id,
                    'title' => $lesson->title,
                    'start' => $lesson->start_date,
                    'end'   => $lesson->end_date,
                ]),
            ]);
        }

        // ==========================
        // Одноразове заняття
        // ==========================
        $plannedLesson = PlannedLesson::create([
            'title'       => match ($validated['lesson_type']) {
                'group' => 'Групове заняття',
                'pair'  => 'Парне заняття',
                'trial' => 'Пробне заняття',
                default => 'Індивідуальне заняття',
            },
            'start_date'  => $start->format('Y-m-d H:i:s'),
            'end_date'    => $start->copy()->addMinutes($duration)->format('Y-m-d H:i:s'),
            'teacher_id'  => $teacherId,
            'student_id'  => $validated['student_id'] ?? null,
            'group_id'    => $validated['group_id'] ?? null,
            'notes'       => $validated['notes'] ?? null,
            'status'      => LessonStatus::Planned->value,
            'lesson_type' => $validated['lesson_type'],
            // 'subscription_template_id' => $template?->id,
        ]);

        // 🔹 Лог: створено одиничне заняття
        LessonActionLogger::log(
            lessonId: $plannedLesson->id,
            action: 'created',
            lessonDatetime: $plannedLesson->start_date,
            newLessonDatetime: null,
            meta: [
                'repeat_weekly' => false,
                'source'        => 'StoreEventController',
            ]
        );

        return response()->json([
            'success' => true,
            'event'   => [
                'id'    => $plannedLesson->id,
                'title' => $plannedLesson->title,
                'start' => $plannedLesson->start_date,
                'end'   => $plannedLesson->end_date,
            ],
        ]);
    }
}
