<?php

namespace App\Http\Controllers\Admin\Teachers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Teachers\UpdateRequest;
use App\Models\Teacher;
use Illuminate\Support\Facades\Storage;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, Teacher $teacher)
    {
        $data = $request->validated();

        if ($request->hasFile('public_photo')) {
            if ($teacher->public_photo) {
                Storage::disk('public')->delete($teacher->public_photo);
            }

            $data['public_photo'] = $request->file('public_photo')->store('teachers', 'public');
        }

        $data['is_public'] = (bool) ($data['is_public'] ?? false);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $teacher->update($data);


        return redirect()
            ->route('admin.teachers.index')
            ->with('success', 'Викладача оновлено');
    }
}
