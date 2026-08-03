<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CoursePaymentController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user?->isStudent(), 403);

        if (! $course->is_published) {
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

        $result = DB::transaction(function () use ($student, $course, $user): array {
            $lockedStudent = Student::query()
                ->lockForUpdate()
                ->findOrFail($student->id);
            $paymentDescription = 'Оплата за "'.$course->title.'"';

            if ($course->isAvailableFor($user)) {
                return ['available' => true];
            }

            $existingPayment = Payment::query()
                ->where('student_id', $lockedStudent->id)
                ->where('status', 'pending')
                ->where('type', 'single')
                ->where('provider', 'monopay')
                ->where('payload->course_id', $course->id)
                ->latest()
                ->first();

            if ($existingPayment) {
                $offerIsCurrent = (float) $existingPayment->amount === (float) $course->price
                    && $existingPayment->description === $paymentDescription;

                if ($offerIsCurrent && $existingPayment->hasReusableMonoPayInvoice()) {
                    return ['payment' => $existingPayment];
                }

                if ($offerIsCurrent) {
                    $existingPayment->failExpiredMonoPayInvoice();
                } else {
                    $existingPayment->update([
                        'status' => 'failed',
                        'payload' => array_merge($existingPayment->payload ?? [], [
                            'offer_changed_locally' => true,
                            'offer_changed_locally_at' => now()->toISOString(),
                        ]),
                    ]);
                }
            }

            return [
                'payment' => Payment::create([
                    'student_id' => $lockedStudent->id,
                    'amount' => $course->price,
                    'currency' => 'UAH',
                    'status' => 'pending',
                    'type' => 'single',
                    'provider' => 'monopay',
                    'provider_order_id' => (string) Str::uuid(),
                    'description' => $paymentDescription,
                    'payload' => [
                        'course_id' => $course->id,
                        'user_id' => $user->id,
                    ],
                ]),
            ];
        });

        if ($result['available'] ?? false) {
            return redirect()
                ->route('courses.show', $course)
                ->with('success', 'У вас вже є доступ до цього курсу.');
        }

        return redirect()->route('student.payments.checkout', $result['payment']);
    }
}
