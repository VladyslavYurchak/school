<?php

namespace App\Http\Controllers\Admin\Teachers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Teachers\UpdateRequest;
use App\Models\Teacher;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, Teacher $teacher)
    {
        $data = $request->validated();
        $teacher->update($data);

        return redirect()->route('admin.teachers.index')->with('success', 'Викладача оновлено');
    }
}
