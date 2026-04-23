<?php

namespace App\Http\Controllers\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Language;

class FilterByLanguageController extends Controller
{
    public function __invoke($languageId)
    {
        return view('admin.course.index', [
            'languages' => Language::all(),
            'courses' => Course::with('language')
                ->withCount('lessons')
                ->where('language_id', $languageId)
                ->paginate(10),
        ]);
    }
}
