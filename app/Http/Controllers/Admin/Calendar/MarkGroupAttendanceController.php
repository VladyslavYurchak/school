<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Enums\LessonLogStatus;
use App\Enums\LessonStatus;
use App\Http\Controllers\Controller;
use App\Models\LessonLog;
use App\Models\Group;
use App\Models\PlannedLesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MarkGroupAttendanceController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:groups,id',
            'lesson_id' => 'required|exists:planned_lessons,id',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'present_students' => 'array',
            'present_students.*' => 'exists:students,id',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $group  = Group::with('students')->findOrFail($request->group_id);
                $lesson = PlannedLesson::with('teacher')->findOrFail($request->lesson_id);

// teacher_id: з плану → з групи → з користувача
                $teacherId = $lesson->teacher_id
                    ?? $group->teacher_id
                    ?? optional(auth()->user()->teacher)->id;

// Позначаємо урок виконаним
                if ($lesson->status !== \App\Enums\LessonStatus::Completed->value) {
                    $lesson->status = \App\Enums\LessonStatus::Completed->value;
                    $lesson->save();
                }

                $present = array_map('intval', $request->present_students ?? []);
                $presentSet = array_flip($present);

                $duration = $lesson->duration ?? 60;
                $type = in_array($lesson->lesson_type, ['group', 'pair'], true)
                    ? $lesson->lesson_type
                    : 'group';

                $teacher  = $lesson->teacher;

// Базова ставка за ЗАНЯТТЯ
                $basis    = 'per_lesson';
                $baseRate = (float) (
                $type === 'pair'
                    ? ($teacher?->pair_lesson_price ?? 0)
                    : ($teacher?->group_lesson_price ?? 0)
                );

// --- РОЗПОДІЛ СТАВКИ МІЖ УСІМА СТУДЕНТАМИ, НЕЗАЛЕЖНО ВІД ПРИСУТНОСТІ ---
                $totalStudents = max(1, $group->students->count());
                $totalCents    = (int) round($baseRate * 100);
                $shareCents    = intdiv($totalCents, $totalStudents);
                $remainder     = $totalCents % $totalStudents; // перші $remainder студентів отримають +0.01 грн

                $idx = 0;
                foreach ($group->students as $student) {
                    $studentId = (int) $student->id;
                    $isPresent = isset($presentSet[$studentId]);

                    $status = $isPresent
                        ? \App\Enums\LessonLogStatus::Completed->value
                        : \App\Enums\LessonLogStatus::Charged->value;

                    // рівна частка кожному, з урахуванням копійок
                    $payoutCents = $shareCents + ($idx < $remainder ? 1 : 0);
                    $payout = $payoutCents / 100;

                    $existing = \App\Models\LessonLog::where('lesson_id', $lesson->id)
                        ->where('student_id', $studentId)
                        ->first();

                    $payload = [
                        'lesson_id'   => $lesson->id,
                        'student_id'  => $studentId,
                        'teacher_id'  => $teacherId,
                        'lesson_type' => $type,
                        'group_id'    => $group->id,
                        'date'        => $request->date,
                        'time'        => $request->time . ':00',
                        'duration'    => $duration,
                        'status'      => $status,
                        'notes'       => $lesson->notes,

                        // snapshot оплати
                        'teacher_rate_amount_at_charge' => $baseRate,   // ставка за урок (загальна)
                        'teacher_payout_basis'          => $basis,      // per_lesson
                        'teacher_payout_amount'         => $payout,     // частка кожного
                        'charged_at'                    => now(),
                    ];

                    $existing ? $existing->update($payload) : \App\Models\LessonLog::create($payload);

                    $idx++;
                  }
            });

            return response()->json(['success' => true, 'message' => 'Відвідуваність збережена']);
        } catch (\Exception $e) {
            Log::error('MarkGroupAttendance error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Помилка при збереженні'], 500);
        }
    }
}
