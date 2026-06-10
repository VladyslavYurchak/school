<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LessonPaymentController extends Controller
{
    public function store(Request $request, Lesson $lesson): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user, 403);
        abort_unless($lesson->is_published, 404);

        if ($lesson->isAvailableFor($user)) {
            return redirect()
                ->route('courses.lessons.show', [$lesson->course, $lesson])
                ->with('success', 'У вас вже є доступ до цього уроку.');
        }

        if (!$lesson->price) {
            return redirect()
                ->route('courses.show', $lesson->course)
                ->with('error', 'Урок не продається окремо.');
        }

        $student = $user->student;

        if (!$student) {
            $student = Student::create([
                'user_id' => $user->id,
                'first_name' => $user->name ?? 'Користувач',
                'last_name' => '',
                'email' => $user->email,
                'is_active' => true,
                'start_date' => now()->toDateString(),
            ]);
        }

        $payment = Payment::create([
            'student_id' => $student->id,
            'amount' => $lesson->price,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'single',
            'provider' => 'monopay',
            'provider_order_id' => (string) Str::uuid(),
            'description' => 'Оплата уроку: ' . $lesson->title,
            'payload' => [
                'lesson_id' => $lesson->id,
                'user_id' => $user->id,
            ],
        ]);

        return redirect()->route('student.payments.checkout', $payment);
    }
}
