<?php

namespace App\Http\Controllers\Admin\Teachers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Teachers\StoreRequest;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $data = $request->validated();

        $user = User::findOrFail($data['user_id']);

        // оновлюємо роль користувача
        $user->role = 'teacher';
        $user->save();

        // створюємо запис викладача
        Teacher::create([
            'user_id'            => $user->id,
            'lesson_price'       => $data['lesson_price'] ?? null,
            'note'               => $data['note'] ?? null,
            'is_active'          => $data['is_active'],

            'group_lesson_price' => $data['group_lesson_price'] ?? 0,
            'trial_lesson_price' => $data['trial_lesson_price'] ?? 0,
            'pair_lesson_price'  => $data['pair_lesson_price'] ?? 0,
        ]);


        return redirect()->route('admin.teachers.index')->with('success', 'Викладача додано');
    }
}
