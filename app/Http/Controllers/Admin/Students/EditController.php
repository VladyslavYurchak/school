<?php

namespace App\Http\Controllers\Admin\Students;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use App\Models\User;

class EditController extends Controller
{
    public function __invoke(Student $student)
    {
        $teachers = Teacher::all();
        $subscriptionTemplates = SubscriptionTemplate::orderBy('title')->get();

        return view('admin.students.edit', compact('student', 'teachers', 'subscriptionTemplates'));
    }
}
