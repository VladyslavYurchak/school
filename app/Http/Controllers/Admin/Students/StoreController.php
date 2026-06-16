<?php

namespace App\Http\Controllers\Admin\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Students\StoreRequest;
use App\Models\Student;
use App\Models\User;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $data = $request->validated();

        Student::create($data);

        if (!empty($data['user_id'])) {
            User::where('id', $data['user_id'])->update([
                'role' => 'student',
            ]);
        }

        return redirect()
            ->route('admin.students.index')
            ->with('success', 'Учня успішно додано');
    }
}
