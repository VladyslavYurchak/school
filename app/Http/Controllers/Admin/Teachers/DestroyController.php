<?php

namespace App\Http\Controllers\Admin\Teachers;

use App\Http\Controllers\Controller;
use App\Models\Teacher;

class DestroyController extends Controller
{
    public function __invoke(Teacher $teacher)
    {
        $teacher->update(['is_active' => false]);

        return redirect()
            ->route('admin.teachers.index')
            ->with('success', 'Викладача деактивовано. Історію занять і розрахунків збережено.');
    }
}
