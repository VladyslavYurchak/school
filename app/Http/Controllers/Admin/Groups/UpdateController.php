<?php

namespace App\Http\Controllers\Admin\Groups;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Groups\UpdateRequest;
use App\Models\Group;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, Group $group)
    {
        $validated = $request->validated();

        $validated['type'] = $validated['type'] ?? $group->type ?? 'group';

        $students = $group->students()->with('subscriptionTemplate')->get();

        if ($validated['type'] === 'pair' && $students->count() > 2) {
            return redirect()->back()->withInput()->with(
                'error',
                'Неможливо змінити тип на парний: у групі більше двох студентів.'
            );
        }

        $hasMismatchedSubscription = $students->contains(function ($student) use ($validated) {
            return $student->subscriptionTemplate?->type !== $validated['type'];
        });

        if ($hasMismatchedSubscription) {
            return redirect()->back()->withInput()->with(
                'error',
                'Тип групи не відповідає призначеним абонементам студентів.'
            );
        }

        $group->update($validated);

        return redirect()
            ->route('admin.groups.index')
            ->with('success', 'Групу оновлено успішно.');
    }
}
