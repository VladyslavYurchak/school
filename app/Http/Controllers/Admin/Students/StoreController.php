<?php

namespace App\Http\Controllers\Admin\Students;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'first_name'        => 'required|string|max:255',
            'last_name'         => 'required|string|max:255',
            'phone'             => 'nullable|string|max:20',
            'teacher_id'        => 'nullable|exists:teachers,id',
            'subscription_id'   => 'nullable|exists:subscription_templates,id',
            'birth_date'        => 'nullable|date',
            'parent_contact'    => 'nullable|string|max:255',
            'is_active'         => 'nullable|boolean',
            'note'              => 'nullable|string',
            'email'             => 'nullable|email|max:255',
        ]);

        Student::create($data);


        return redirect()->route('admin.students.index')->with('success', 'Учня успішно додано');
    }
}
