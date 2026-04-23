<?php

namespace App\Http\Controllers\Admin\SchoolRules;

use App\Http\Controllers\Controller;
use App\Models\SchoolRule;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        SchoolRule::create($data);

        return redirect()
            ->route('admin.school-rules.index')
            ->with('success', 'Правило додано');
    }
}
