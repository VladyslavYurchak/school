<?php

namespace App\Http\Controllers\Admin\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Students\UpdateRequest;
use App\Models\Student;
use App\Models\User;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, Student $student)
    {
        $data = $request->validated();

        $student->update($data);

        if (!empty($data['user_id'])) {
            User::where('id', $data['user_id'])->update([
                'role' => 'student',
            ]);
        }

        return redirect()
            ->route('admin.students.index')
            ->with('success', 'Дані учня оновлено');
    }
}
