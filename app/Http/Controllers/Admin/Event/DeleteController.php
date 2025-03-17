<?php

namespace App\Http\Controllers\Admin\Event;
use App\Http\Controllers\Admin\Post\BaseController;
use App\Models\Event;
use App\Models\Post;


class DeleteController extends BaseController
{

    public function __invoke(Event $event)
    {
        $event->delete();
        return redirect()->route('admin.event.index')->with('success', 'Подія успішно видалена');
    }
}
