<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\UpdateSettingsRequest;

class UpdateSettingsController extends Controller
{
    public function __invoke(UpdateSettingsRequest $request)
    {
        $request->user()->teacher->update([
            'meeting_url' => $request->validated('meeting_url') ?: null,
        ]);

        return back()->with('success', 'Налаштування викладача збережено.');
    }
}
