<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LessonPaymentController extends Controller
{
    public function store(Request $request, Lesson $lesson): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user?->isStudent(), 403);
        $lesson->loadMissing('course');

        abort_unless($lesson->course?->is_published, 404);
        abort_unless($lesson->is_published, 404);

        if ($lesson->isAvailableFor($user)) {
            return redirect()
                ->route('courses.lessons.show', [$lesson->course, $lesson])
                ->with('success', 'У вас вже є доступ до цього уроку.');
        }

        if (! $lesson->isPaid()) {
            return redirect()
                ->route('courses.show', $lesson->course)
                ->with('error', 'Урок не продається окремо.');
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

        $result = DB::transaction(function () use ($student, $lesson, $user): array {
            $lockedStudent = Student::query()
                ->lockForUpdate()
                ->findOrFail($student->id);
            $paymentDescription = 'Оплата за "'.$lesson->title.'"';

            if ($lesson->isAvailableFor($user)) {
                return ['available' => true];
            }

            $existingPayment = Payment::query()
                ->where('student_id', $lockedStudent->id)
                ->where('status', 'pending')
                ->where('type', 'single')
                ->where('provider', 'monopay')
                ->where('payload->lesson_id', $lesson->id)
                ->latest()
                ->first();

            if ($existingPayment) {
                $offerIsCurrent = (float) $existingPayment->amount === (float) $lesson->price
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
                    'amount' => $lesson->price,
                    'currency' => 'UAH',
                    'status' => 'pending',
                    'type' => 'single',
                    'provider' => 'monopay',
                    'provider_order_id' => (string) Str::uuid(),
                    'description' => $paymentDescription,
                    'payload' => [
                        'lesson_id' => $lesson->id,
                        'user_id' => $user->id,
                    ],
                ]),
            ];
        });

        if ($result['available'] ?? false) {
            return redirect()
                ->route('courses.lessons.show', [$lesson->course, $lesson])
                ->with('success', 'У вас вже є доступ до цього уроку.');
        }

        return redirect()->route('student.payments.checkout', $result['payment']);
    }
}
