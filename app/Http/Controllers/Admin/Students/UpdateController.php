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

        if (!empty($data['user_id'])) {
            \App\Models\User::where('id', $data['user_id'])->update([
                'role' => 'student',
            ]);
        }
        return redirect()->route('admin.students.index')->with('success', 'Дані учня оновлено');
    }
}
