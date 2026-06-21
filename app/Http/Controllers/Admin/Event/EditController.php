<?php

namespace App\Http\Controllers\Admin\Event;

use App\Http\Controllers\Controller;
use App\Models\Event;

class EditController extends Controller
{
    public function __invoke(Event $event)
    {
        return view('admin.event.edit', compact('event'));
    }
}
