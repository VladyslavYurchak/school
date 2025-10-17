<?php

namespace App\Http\Controllers\Admin\Groups;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Group;

class StoreController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type'       => 'nullable|in:group,pair',
            'teacher_id' => 'required|exists:teachers,id',
            'notes' => 'nullable|string',
        ]);

        Group::create($validated);

        return redirect()
            ->route('admin.groups.index')
            ->with('success', 'Групу успішно створено.');
    }
}
