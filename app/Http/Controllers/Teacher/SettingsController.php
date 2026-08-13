<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless($request->user()?->isTeacher() && $request->user()?->teacher, 403);

        return view('admin.teacher-settings.edit', [
            'teacher' => $request->user()->teacher,
            'telegramAccount' => $request->user()->telegramAccount()->first(),
        ]);
    }
}
