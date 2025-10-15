<?php

namespace App\Http\Controllers\Admin\Groups;

use App\Http\Controllers\Controller;
use App\Models\Group;

class IndexController extends Controller
{
    public function __invoke()
    {
        $groups = Group::withCount('students')->get();

        return view('admin.groups.index', compact('groups'));
    }
}
