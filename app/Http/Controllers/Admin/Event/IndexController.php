<?php

namespace App\Http\Controllers\Admin\Event;

use App\Http\Controllers\Controller;
use App\Models\Event;

class  IndexController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function __invoke()
    {
        $events = Event::query()->paginate(10);
       return view('admin/event/index', compact('events'));
    }
}
