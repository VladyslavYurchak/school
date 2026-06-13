<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Enums\LessonLogStatus;
use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Http\Controllers\Controller;
use App\Models\LessonLog;
use App\Models\Group;
use App\Models\PlannedLesson;
use App\Services\LessonActionLogger;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Admin\Calendar\MarkGroupAttendanceRequest;

class MarkGroupAttendanceController extends Controller
{
    public function __invoke(MarkGroupAttendanceRequest $request)
    {
        $data = $request->validated();

        try {
            DB::transaction(function () use ($data) {
                $user = auth()->user();

                $groupQuery = Group::with('students');

                $lessonQuery = PlannedLesson::with('teacher');

                if ($user->role === 'teacher') {
                    $teacherId = optional($user->teacher)->id;

                    if (!$teacherId) {
                        abort(403);
                    }

                    $groupQuery->where('teacher_id', $teacherId);
                    $lessonQuery->where('teacher_id', $teacherId);
                }

                $group = $groupQuery->findOrFail($data['group_id']);

                $lesson = $lessonQuery
                    ->where('group_id', $group->id) // дуже важливо: урок має належати цій групі
                    ->findOrFail($data['lesson_id']);

// teacher_id: з плану → з групи → з користувача
                $teacherId = $lesson->teacher_id
                    ?? $group->teacher_id
                    ?? optional(auth()->user()->teacher)->id;

// Позначаємо урок виконаним
                if ($lesson->status !== LessonStatus::Completed) {
                    $lesson->status = LessonStatus::Completed;
                    $lesson->save();
                }

                LessonActionLogger::log(
                    lessonId: $lesson->id,
                    action: 'completed',
                    lessonDatetime: $lesson->start_date,
                    newLessonDatetime: null,
                    meta: [
                        'group_id' => $group->id,
                    ]
                );

                $present = array_map('intval', $data['present_students'] ?? []);

                $presentSet = array_flip($present);

                $duration = $lesson->duration ?? 60;

                $lessonType = $lesson->lesson_type;
                $type = $lessonType instanceof LessonType ? $lessonType->value : (string) $lessonType;

                $teacher  = $lesson->teacher;

                $basis    = 'per_lesson';

                $baseRate = (float) (
                $lessonType === LessonType::Pair
                    ? ($teacher?->pair_lesson_price ?? 0)
                    : ($teacher?->group_lesson_price ?? 0)
                );

// --- РОЗПОДІЛ СТАВКИ МІЖ УСІМА СТУДЕНТАМИ, НЕЗАЛЕЖНО ВІД ПРИСУТНОСТІ ---
                $totalStudents = max(1, $group->students->count());
                $totalCents    = (int) round($baseRate * 100);
                $shareCents    = intdiv($totalCents, $totalStudents);
                $remainder     = $totalCents % $totalStudents; // перші $remainder студентів отримають +0.01 грн

                $idx = 0;

                $existingLogs = LessonLog::query()
                    ->where('lesson_id', $lesson->id)
                    ->get()
                    ->keyBy('student_id');


                foreach ($group->students as $student) {
                    $studentId = (int) $student->id;
                    $isPresent = isset($presentSet[$studentId]);

                    $status = $isPresent
                        ? LessonLogStatus::Completed
                        : LessonLogStatus::Charged;

                    // рівна частка кожному, з урахуванням копійок
                    $payoutCents = $shareCents + ($idx < $remainder ? 1 : 0);
                    $payout = $payoutCents / 100;

                    $existing = $existingLogs->get($studentId);

                    $payload = [
                        'lesson_id'   => $data['lesson_id'],
                        'student_id'  => $studentId,
                        'teacher_id'  => $teacherId,
                        'lesson_type' => $type,
                        'group_id'    => $data['group_id'],
                        'date'        => $data['date'],
                        'time'        => $data['time'] . ':00',
                        'duration'    => $duration,
                        'status'      => $status,
                        'notes'       => $lesson->notes,

                        'teacher_rate_amount_at_charge' => $baseRate,
                        'teacher_payout_basis'          => $basis,
                        'teacher_payout_amount'         => $payout,
                        'charged_at'                    => now(),
                    ];

                    $existing ? $existing->update($payload) : LessonLog::create($payload);

                    $idx++;
                }
            });

            return response()->json(['success' => true, 'message' => 'Відвідуваність збережена']);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Group or lesson was not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('MarkGroupAttendance error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Помилка при збереженні'], 500);
        }
    }
}
