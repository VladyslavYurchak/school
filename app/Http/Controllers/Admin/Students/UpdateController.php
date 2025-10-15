<?php

namespace App\Http\Controllers\Admin\Students;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __invoke(Request $request, Student $student)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'birth_date' => 'nullable|date',
            'parent_contact' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'note' => 'nullable|string',
            'teacher_id' => 'nullable|exists:teachers,id',
            'subscription_id' => 'nullable|exists:subscription_templates,id',
        ]);

        $student->update($data);

        return redirect()->route('admin.students.index')->with('success', 'Дані учня оновлено');
    }
}
