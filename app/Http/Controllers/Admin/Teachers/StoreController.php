<?php

namespace App\Http\Controllers\Admin\Teachers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Teachers\StoreRequest;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('public_photo')) {
            $data['public_photo'] = $request->file('public_photo')->store('teachers', 'public');
        }

        $data['public_bio'] = $this->cleanPublicBio($data['public_bio'] ?? null);

        DB::transaction(function () use ($data): void {
            $user = User::query()->lockForUpdate()->findOrFail($data['user_id']);

            if (! $user->isAdmin()) {
                $user->update(['role' => 'teacher']);
            }

            Teacher::create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
                'email' => $user->email,
                'meeting_url' => $data['meeting_url'] ?? null,
                'lesson_price' => $data['lesson_price'] ?? null,
                'note' => $data['note'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),

                'group_lesson_price' => $data['group_lesson_price'] ?? 0,
                'trial_lesson_price' => $data['trial_lesson_price'] ?? 0,
                'pair_lesson_price' => $data['pair_lesson_price'] ?? 0,

                'public_photo' => $data['public_photo'] ?? null,
                'public_position' => $data['public_position'] ?? null,
                'public_bio' => $data['public_bio'] ?? null,
                'public_details' => $data['public_details'] ?? null,
                'is_public' => (bool) ($data['is_public'] ?? false),
                'public_sort_order' => $data['public_sort_order'] ?? 0,
            ]);
        });

        return redirect()
            ->route('admin.teachers.index')
            ->with('success', 'Викладача додано');
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
