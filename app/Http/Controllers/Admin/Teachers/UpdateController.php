<?php

namespace App\Http\Controllers\Admin\Teachers;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __invoke(Request $request, Teacher $teacher)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|unique:teachers,email,' . $teacher->id,
            'lesson_price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'is_active' => 'required|boolean',
            'group_lesson_price' => 'nullable|numeric|min:0',
            'trial_lesson_price' => 'nullable|numeric|min:0',
            'pair_lesson_price' => 'nullable|numeric|min:0'
        ]);

        $teacher->update($data);

        return redirect()->route('admin.teachers.index')->with('success', 'Викладача оновлено');
    }
}
