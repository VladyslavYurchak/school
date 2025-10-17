<?php

namespace App\Http\Controllers\Admin\Groups;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Student;
use App\Models\SubscriptionTemplate;
use Illuminate\Http\Request;

class AddStudentToGroupController extends Controller
{
    public function __invoke(Request $request, Group $group)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        // 1) Дістаємо студента
        $student = Student::findOrFail($request->student_id);

        // 2) Переконуємось, що у групи валідний тип
        $groupType = $group->type ?? null; // очікуємо 'group' або 'pair'
        if (!in_array($groupType, ['group', 'pair'], true)) {
            return redirect()->back()->with('error', 'Невідомий тип групи. Дозволено лише: group або pair.');
        }

        // 3) У студента має бути вибраний абонемент
        if (empty($student->subscription_id)) {
            return redirect()->back()->with('error', 'Неможливо додати: у студента не вибрано абонемент.');
        }

        // 4) Перевіряємо тип абонементу студента
        $tpl = SubscriptionTemplate::find($student->subscription_id);
        if (!$tpl) {
            return redirect()->back()->with('error', 'Абонемент студента не знайдено.');
        }

        if ($tpl->type !== $groupType) {
            return redirect()->back()->with(
                'error',
                "Тип абонементу студента ({$tpl->type}) не відповідає типу групи ({$groupType})."
            );
        }

        // 5) Ок — додаємо до групи
        $student->group_id = $group->id;
        $student->save();

        return redirect()->back()->with('success', 'Студента додано до групи.');
    }
}
