<?php

namespace App\Http\Controllers\Admin\Event;

use App\Models\Event;

class DeleteController extends BaseController
{
    public function __invoke(Event $event)
    {
        $event->delete();
        return redirect()->route('admin.event.index')->with('success', 'Подія успішно видалена');
    }
}
