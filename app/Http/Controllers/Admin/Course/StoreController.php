<?php

namespace App\Http\Controllers\Admin\Course;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Course\StoreRequest;
use App\Models\Course;
use App\Models\Language;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();


        Course::create($request->only('title', 'language_id', 'price', 'is_published'));

        return redirect()->route('admin.course.index')->with('success', 'Курс створено!');
    }
}
