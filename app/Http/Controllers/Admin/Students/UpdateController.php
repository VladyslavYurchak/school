<?php

namespace App\Http\Controllers\Admin\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Students\UpdateRequest;
use App\Models\Student;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, Student $student)
    {
        $student->update($request->validated());

        return redirect()->route('admin.students.index')->with('success', 'Дані учня оновлено');
    }
}
