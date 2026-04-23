<?php

namespace App\Http\Controllers\Admin\SchoolRules;

use App\Http\Controllers\Controller;

class CreateController extends Controller
{
    public function __invoke()
    {
        return view('admin.school_rules.create');
    }
}
