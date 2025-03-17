<?php

namespace App\Http\Controllers\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Language;

class CreateController extends Controller
{
    public function __invoke()
    {
        return view('admin.course.create', ['languages' => Language::all()]);
    }
}
