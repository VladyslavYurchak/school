<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\LessonLog;
use App\Models\PlannedLesson;
use Carbon\Carbon;
use Illuminate\Http\Request;


class StoreController extends Controller
{

    public function __invoke()
    {
        return view('admin.index');
    }
}
