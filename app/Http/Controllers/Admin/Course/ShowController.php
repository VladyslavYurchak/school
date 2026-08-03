<?php

namespace App\Http\Controllers\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;

class ShowController extends Controller
{
    public function __invoke(Course $course)
    {
        $course->load(['lessons' => function ($query) {
            $query
                ->withCount(['contentBlocks', 'vocabularyItems', 'exercises', 'tests'])
                ->orderBy('position', 'asc');
        }]);

        return view('admin.course.show', compact('course'));
    }
}
