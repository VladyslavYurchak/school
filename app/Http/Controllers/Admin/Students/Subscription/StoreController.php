<?php

namespace App\Http\Controllers\Admin\Students\Subscription;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Students\Subscription\StoreRequest;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\SubscriptionTemplate;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request, Student $student): RedirectResponse
    {
        $data = $request->validated();

        if ($data['type'] === 'subscription') {
            if (empty($data['subscription_template_id'])) {
                return redirect()->back()->with('error', 'Оберіть абонемент для студента.');
            }

            if (empty($data['month'])) {
                return redirect()->back()->with('error', 'Оберіть місяць для абонементу.');
            }

            $startDate = Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth();
            $endDate = (clone $startDate)->endOfMonth();

            $exists = StudentSubscription::query()
                ->where('student_id', $student->id)
                ->where('type', 'subscription')
                ->where('start_date', $startDate->toDateString())
                ->where('end_date', $endDate->toDateString())
                ->whereIn('status', ['pending', 'active'])
                ->exists();

            if ($exists) {
                return redirect()->back()->with('error', 'Абонемент на цей місяць уже існує.');
            }

            $template = SubscriptionTemplate::findOrFail($data['subscription_template_id']);

            StudentSubscription::create([
                'student_id' => $student->id,
                'subscription_template_id' => $template->id,
                'payment_id' => null,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'price' => $template->price,
                'type' => 'subscription',
                'status' => 'active',
                'lessons_total' => $template->lessons_per_week * 4,
                'lessons_used' => 0,
                'paid_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Абонемент успішно додано.');
        }

        if ($data['type'] === 'single') {
            if (empty($data['price']) || $data['price'] <= 0) {
                return redirect()->back()->with('error', 'Вкажіть коректну ціну для поразової оплати.');
            }

            $date = !empty($data['single_date'])
                ? Carbon::parse($data['single_date'])->toDateString()
                : now()->toDateString();

            StudentSubscription::create([
                'student_id' => $student->id,
                'subscription_template_id' => null,
                'payment_id' => null,
                'start_date' => $date,
                'end_date' => $date,
                'price' => $data['price'],
                'type' => 'single',
                'status' => 'active',
                'lessons_total' => 1,
                'lessons_used' => 0,
                'paid_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Поразову оплату успішно додано.');
        }

        return redirect()->back()->with('error', 'Невідомий тип оплати.');
    }
}
