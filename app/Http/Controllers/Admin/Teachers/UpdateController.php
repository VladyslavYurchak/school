<?php

namespace App\Http\Controllers\Admin\Teachers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Teachers\UpdateRequest;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
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

        $data['public_bio'] = $this->cleanPublicBio($data['public_bio'] ?? null);
        $data['is_public'] = (bool) ($data['is_public'] ?? false);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        DB::transaction(function () use ($teacher, $data): void {
            $lockedTeacher = Teacher::query()
                ->lockForUpdate()
                ->findOrFail($teacher->id);
            $user = $lockedTeacher->user()
                ->lockForUpdate()
                ->firstOrFail();

            if (! $user->isAdmin()) {
                $user->update(['role' => 'teacher']);
            }

            $lockedTeacher->update($data);
        });

        return redirect()
            ->route('admin.teachers.edit', $teacher)
            ->with('success', 'Дані викладача збережено');
    }

    private function cleanPublicBio(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $withoutScripts = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $html);
        $clean = strip_tags($withoutScripts, '<p><br><strong><b><em><i><ul><ol><li><blockquote>');

        return preg_replace('/<([a-z][a-z0-9]*)\b[^>]*>/i', '<$1>', $clean);
    }
}
