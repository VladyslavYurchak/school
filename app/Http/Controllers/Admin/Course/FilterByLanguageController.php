<?php

namespace App\Http\Controllers\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Language;
use Illuminate\Http\Request;

class FilterByLanguageController extends Controller
{
    public function __invoke($languageId)
    {
        return view('admin.courses.index', [
            'languages' => Language::all(),
            'courses' => Course::where('language_id', $languageId)->get(),
        ]);
    }
}
