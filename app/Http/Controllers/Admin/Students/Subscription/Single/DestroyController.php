<?php

namespace App\Http\Controllers\Admin\Students\Subscription\Single;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentSubscription;

class DestroyController extends Controller
{
    public function __invoke(Student $student, StudentSubscription $payment)
    {
        // гарантуємо належність та тип
        if ($payment->student_id !== $student->id || $payment->type !== 'single') {
            return redirect()->back()->with('error', 'Оплату не знайдено або вона не є поразовою.');
        }

        $payment->delete();

        return redirect()->back()->with('success', 'Поразову оплату скасовано.');
    }
}
