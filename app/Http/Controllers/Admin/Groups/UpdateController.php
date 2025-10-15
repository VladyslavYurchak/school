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
            'name' => 'required|string|max:255',
            'teacher_id' => 'required|exists:teachers,id',
            'note' => 'nullable|string',
            // додайте інші поля групи, якщо потрібно
        ]);

        $group->update($validated);

        return redirect()->route('admin.groups.index')->with('success', 'Групу оновлено успішно.');
    }
}
