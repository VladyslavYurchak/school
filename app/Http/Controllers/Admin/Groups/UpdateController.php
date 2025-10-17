<?php

namespace App\Http\Controllers\Admin\Groups;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __invoke(Request $request, Group $group)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'type'       => 'nullable|in:group,pair',
            'teacher_id' => 'required|exists:teachers,id',
            'notes'      => 'nullable|string',
        ]);

        // якщо тип не передано — залишаємо старий або дефолт 'group'
        $validated['type'] = $validated['type'] ?? $group->type ?? 'group';

        $group->update($validated);

        return redirect()
            ->route('admin.groups.index')
            ->with('success', 'Групу оновлено успішно.');
    }
}
