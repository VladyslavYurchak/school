<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Calendar\MarkAsRescheduledRequest;
use App\Models\LessonLog;
use App\Models\PlannedLesson;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MarkAsRescheduledController extends Controller
{
    public function __invoke($id, MarkAsRescheduledRequest $request)
    {
        $data = $request->validated();

        try {
            return DB::transaction(function () use ($id, $data) {
                $lesson = PlannedLesson::findOrFail($id);

                $initiator  = $data['initiator'];                 // teacher|student|admin
                $newDate    = $data['new_date'];
                $newTime    = $data['new_time'];
                $newDateTime = Carbon::parse("$newDate $newTime");

                $oldStart = $lesson->start_date;

                // 🔢 Ліміт переносів для студента (2/місяць по новій даті)
                if ($initiator === 'student' && $lesson->student_id) {
                    $reschedulesThisMonth = PlannedLesson::withTrashed()
                        ->where('student_id', $lesson->student_id)
                        ->where('status', LessonStatus::Rescheduled->value)
                        ->where('initiator', 'student')
                        ->whereBetween('start_date', [
                            $newDateTime->copy()->startOfMonth(),
                            $newDateTime->copy()->endOfMonth(),
                        ])
                        ->count();

                    if ($reschedulesThisMonth >= 2) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Учень вже використав ліміт на 2 переноси цього місяця.'
                        ], 403);
                    }
                }

                // 📌 Позначаємо старе заняття як перенесене
                $lesson->update([
                    'status'    => LessonStatus::Rescheduled->value,
                    'initiator' => $initiator,
                ]);

                LessonLog::where('student_id', $lesson->student_id)
                    ->whereDate('date', $oldStart->toDateString())
                    ->whereTime('time', $oldStart->format('H:i:s'))       // 'H:i:s' важливо!// 'Y-m-d'
                    ->delete();

                $start = Carbon::parse($lesson->start_date);
                $end   = Carbon::parse($lesson->end_date);
                $durationMinutes = $lesson->duration ?? $start->diffInMinutes($end);
                $durationMinutes = max(15, $durationMinutes);

                // 🆕 Створюємо нове заняття (копіюємо корисні поля)
                PlannedLesson::create([
                    'title'       => $lesson->title,
                    'student_id'  => $lesson->student_id,
                    'teacher_id'  => $lesson->teacher_id,
                    'group_id'    => $lesson->group_id,
                    'start_date'  => $newDateTime,
                    'end_date'    => $newDateTime->copy()->addMinutes($durationMinutes),
                    'status'      => LessonStatus::Planned->value,
                    'initiator'   => null, // нове — ще не перенесене
                    'lesson_type' => $lesson->lesson_type ?? LessonType::Individual->value,
                    'duration'    => $lesson->duration ?? $durationMinutes,
                    'notes'       => $lesson->notes,
                ]);


                // мʼяке видалення старого (якщо ввімкнений SoftDeletes)
                $lesson->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Заняття перенесено на нову дату.',
                ]);
            });
        } catch (\Exception $e) {
            Log::error('MarkAsRescheduledController error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Помилка при перенесенні заняття.',
            ], 500);
        }
    }
}
