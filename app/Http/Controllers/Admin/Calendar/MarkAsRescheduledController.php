<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Calendar\MarkAsRescheduledRequest;
use App\Models\LessonLog;
use App\Models\PlannedLesson;
use App\Models\Teacher;
use App\Services\Calendar\CalendarAvailabilityService;
use App\Services\LessonActionLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class MarkAsRescheduledController extends Controller
{
    public function __invoke(
        $id,
        MarkAsRescheduledRequest $request,
        CalendarAvailabilityService $availability
    )
    {
        $data = $request->validated();

        try {
            $result = DB::transaction(function () use ($id, $data, $availability) {
                $query = PlannedLesson::query()
                    ->whereKey((int) $id);

                $user = auth()->user();

                if ($user->role === 'teacher') {
                    $teacherId = optional($user->teacher)->id;

                    if (!$teacherId) {
                        abort(403);
                    }

                    $query->where('teacher_id', $teacherId);
                }

                $lesson = $query
                    ->lockForUpdate()
                    ->first();

                if (!$lesson) {
                    return [
                        'status'  => Response::HTTP_NOT_FOUND,
                        'success' => false,
                        'message' => 'Заняття з таким ID не знайдено.',
                    ];
                }

                if (!is_null($lesson->group_id)) {
                    return [
                        'status'  => Response::HTTP_UNPROCESSABLE_ENTITY,
                        'success' => false,
                        'message' => 'Цей ендпойнт призначений для індивідуальних/пробних. Для груп/пар — використай груповий контролер перенесення.',
                    ];
                }

                Teacher::query()->lockForUpdate()->findOrFail($lesson->teacher_id);

                $initiator = $user->role === 'teacher'
                    ? 'teacher'
                    : $data['initiator'];

                $newDateTime = Carbon::parse($data['new_date'].' '.$data['new_time']);

                if ($initiator === 'student' && $lesson->student_id) {
                    $reschedulesThisMonth = PlannedLesson::withTrashed()
                        ->where('student_id', $lesson->student_id)
                        ->where('status', LessonStatus::Rescheduled)
                        ->where('initiator', 'student')
                        ->whereBetween('start_date', [
                            $newDateTime->copy()->startOfMonth(),
                            $newDateTime->copy()->endOfMonth(),
                        ])
                        ->count();

                    if ($reschedulesThisMonth >= 2) {
                        return [
                            'status'  => Response::HTTP_FORBIDDEN,
                            'success' => false,
                            'message' => 'Учень вже використав ліміт на 2 переноси цього місяця.',
                        ];
                    }
                }

                $oldStart = $lesson->start_date;
                $oldEnd = $lesson->end_date;
                $duration = max(
                    15,
                    Carbon::parse($oldStart)->diffInMinutes(Carbon::parse($oldEnd))
                );
                $newEnd = $newDateTime->copy()->addMinutes($duration);

                if ($availability->teacherHasOverlap(
                    (int) $lesson->teacher_id,
                    $newDateTime,
                    $newEnd,
                    (int) $lesson->id
                )) {
                    return [
                        'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                        'success' => false,
                        'message' => 'Викладач уже має інше заняття в цей час.',
                    ];
                }

                if (
                    $lesson->student_id
                    && $availability->studentHasOverlap(
                        (int) $lesson->student_id,
                        $newDateTime,
                        $newEnd,
                        (int) $lesson->id
                    )
                ) {
                    return [
                        'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                        'success' => false,
                        'message' => 'Учень уже має інше заняття в цей час.',
                    ];
                }

                $lesson->update([
                    'status'    => LessonStatus::Rescheduled,
                    'initiator' => $initiator,
                ]);

                $deletedLogs = LessonLog::query()
                    ->where('lesson_id', $lesson->id)
                    ->delete();

                PlannedLesson::create([
                    'title'       => $lesson->title,
                    'student_id'  => $lesson->student_id,
                    'teacher_id'  => $lesson->teacher_id,
                    'group_id'    => $lesson->group_id,
                    'start_date'  => $newDateTime,
                    'end_date'    => $newEnd,
                    'status'      => LessonStatus::Planned,
                    'initiator'   => null,
                    'lesson_type' => $lesson->lesson_type ?? LessonType::Individual,
                    'notes'       => $lesson->notes,
                ]);

                LessonActionLogger::log(
                    lessonId: $lesson->id,
                    action: 'rescheduled',
                    lessonDatetime: $oldStart,
                    newLessonDatetime: $newDateTime,
                    meta: [
                        'initiator' => $initiator,
                    ]
                );

                $lesson->delete();

                return [
                    'status'  => Response::HTTP_OK,
                    'success' => true,
                    'message' => 'Заняття перенесено на нову дату.',
                    'meta'    => [
                        'old_lesson_id' => $lesson->id,
                        'deleted_logs'  => $deletedLogs,
                    ],
                ];
            });

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'meta'    => $result['meta'] ?? null,
            ], $result['status']);

        } catch (\Throwable $e) {
            Log::error('MarkAsRescheduledController error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'lesson_id' => $id,
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Помилка при перенесенні заняття.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
