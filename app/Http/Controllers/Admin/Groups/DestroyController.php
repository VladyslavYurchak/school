<?php

namespace App\Http\Controllers\Admin\Groups;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class DestroyController extends Controller
{
    public function __invoke(Group $group)
    {
        // Видаляємо групу
        $group->delete();

        // Переходимо назад на список груп з повідомленням
        return redirect()->route('admin.groups.index')->with('success', 'Групу успішно видалено.');
    }
}
