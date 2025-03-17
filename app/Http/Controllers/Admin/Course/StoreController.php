<?php

namespace App\Http\Controllers\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Language;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string', // додали валідацію для description
            'language_id' => 'required|exists:languages,id',
            'price' => 'nullable|numeric|min:0',
            'is_published' => 'boolean',
        ]);

        Course::create($request->only('title', 'language_id', 'price', 'is_published'));

        return redirect()->route('admin.course.index')->with('success', 'Курс створено!');
    }
}
