<?php

namespace App\Http\Controllers\Admin\Course\Lesson\Test;

use App\Http\Controllers\Controller;
use App\Models\LessonTest;
use Illuminate\Http\Request;

class UpdateOrderController extends Controller
{
    public function __invoke(Request $request)
    {
        foreach ($request->input('order') as $item) {
            LessonTest::where('id', $item['id'])->update(['position' => $item['position']]);
        }
        return response()->json(['success' => true]);
    }
}
