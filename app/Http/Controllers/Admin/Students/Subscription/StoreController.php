<?php

namespace App\Http\Controllers\Admin\Students\Subscription;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\SubscriptionTemplate;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __invoke(Request $request, Student $student)
    {
        // Валідація полів
        $data = $request->validate([
            'type' => 'required|in:subscription,single',
            'subscription_template_id' => 'nullable|exists:subscription_templates,id',
            'month' => 'nullable|date_format:Y-m',
            'price' => 'nullable|numeric|min:0',
        ]);

        if ($data['type'] === 'subscription') {
            // Перевірка, що вибрано абонемент
            if (empty($data['subscription_template_id'])) {
                return redirect()->back()->with('error', 'Оберіть абонемент для студента перед оплатою.');
            }

            // Перевірка місяця
            if (empty($data['month'])) {
                return redirect()->back()->with('error', 'Оберіть місяць для абонементу.');
            }

            // Створюємо початок та кінець місяця без зсувів
            $startDate = $data['month'] . '-01';
            $endDate = date('Y-m-t', strtotime($startDate)); // останній день місяця

            // Перевірка, чи вже існує підписка на цей місяць
            $exists = StudentSubscription::where('student_id', $student->id)
                ->where('start_date', $startDate)
                ->where('end_date', $endDate)
                ->exists();

            if ($exists) {
                return redirect()->back()->with('error', 'Підписка на цей місяць уже існує.');
            }

            $template = SubscriptionTemplate::findOrFail($data['subscription_template_id']);
            $price = $template->price;

            StudentSubscription::create([
                'student_id' => $student->id,
                'subscription_template_id' => $data['subscription_template_id'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'price' => $price,
                'type' => 'subscription',
            ]);

            return redirect()->back()->with('success', 'Оплата абонементу успішно додана.');
        }

        if ($data['type'] === 'single') {
            // Для поразової оплати обов'язкова ціна
            if (empty($data['price']) || $data['price'] <= 0) {
                return redirect()->back()->with('error', 'Вкажіть ціну для поразової оплати.');
            }

            // Ставимо поточну дату у форматі Y-m-d
            $now = date('Y-m-d');

            StudentSubscription::create([
                'student_id' => $student->id,
                'subscription_template_id' => null,
                'start_date' => $now,
                'end_date' => $now,
                'price' => $data['price'],
                'type' => 'single',
            ]);

            return redirect()->back()->with('success', 'Поразова оплата успішно додана.');
        }

        return redirect()->back()->with('error', 'Невідомий тип оплати.');
    }
}
