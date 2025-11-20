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

        // якщо тип не передано — залишаємо старий або дефолт 'group'
        $validated['type'] = $validated['type'] ?? $group->type ?? 'group';

        $group->update($validated);

        return redirect()
            ->route('admin.groups.index')
            ->with('success', 'Групу оновлено успішно.');
    }
}
