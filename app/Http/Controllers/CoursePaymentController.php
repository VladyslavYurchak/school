<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CoursePaymentController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user, 403);

        if (!$course->is_published) {
            return redirect()
                ->route('courses.index')
                ->with('error', 'Цей курс недоступний.');
        }

        if ($course->isAvailableFor($user)) {
            return redirect()
                ->route('courses.show', $course)
                ->with('success', 'У вас вже є доступ до цього курсу.');
        }

        if ($course->isFree()) {
            return redirect()
                ->route('courses.show', $course)
                ->with('error', 'Цей курс безкоштовний.');
        }

        $student = Student::firstOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => $user->name ?? 'Користувач',
                'last_name' => '',
                'email' => $user->email,
                'is_active' => true,
                'start_date' => now()->toDateString(),
            ]
        );

        $existingPayment = Payment::query()
            ->where('student_id', $student->id)
            ->where('status', 'pending')
            ->where('type', 'single')
            ->where('provider', 'monopay')
            ->where('payload->course_id', $course->id)
            ->latest()
            ->first();

        if ($existingPayment) {
            if ($existingPayment->hasReusableMonoPayInvoice()) {
                return redirect()->route('student.payments.checkout', $existingPayment);
            }

            $existingPayment->failExpiredMonoPayInvoice();
        }

        $payment = Payment::create([
            'student_id' => $student->id,
            'amount' => $course->price,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'single',
            'provider' => 'monopay',
            'provider_order_id' => (string) Str::uuid(),
            'description' => 'Оплата за "' . $course->title . '"',
            'payload' => [
                'course_id' => $course->id,
                'user_id' => $user->id,
            ],
        ]);

        return redirect()->route('student.payments.checkout', $payment);
    }
}
