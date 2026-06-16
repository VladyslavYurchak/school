<?php

namespace App\Http\Controllers\Admin\Course;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Course\StoreRequest;
use App\Models\Course;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        Course::create($request->validated());

        return redirect()->route('admin.course.index')->with('success', 'Курс створено!');
    }
}
