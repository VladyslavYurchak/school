<?php
namespace App\Http\Controllers\Admin\Groups;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Student;
use App\Models\Teacher;

class EditController extends Controller
{
    public function __invoke(Group $group)
    {
        // Студенти, які вже у групі (з шаблоном абонемента для відображення)
        $students = $group->students()
            ->with(['subscriptionTemplate'])  // щоб у вʼюшці показати тип абонементу
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // Доступні для додавання: активні, без групи, мають абонемент,
        // і тип абонементу збігається з типом групи
        $availableStudents = Student::with(['subscriptionTemplate'])
            ->whereNull('group_id')
            ->where('is_active', true)
            ->whereNotNull('subscription_id')
            ->whereHas('subscriptionTemplate', function ($q) use ($group) {
                $q->where('type', $group->type); // 'group' або 'pair'
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // Усі викладачі (для селекта керівника групи)
        $teachers = Teacher::orderBy('last_name')->orderBy('first_name')->get();

        // Для парної групи: не дозволяти додавати більше 2-х студентів
        $canAddMore = !($group->type === 'pair' && $students->count() >= 2);

        return view('admin.groups.edit', compact(
            'group',
            'teachers',
            'availableStudents',
            'students',
            'canAddMore'
        ));
    }
}
