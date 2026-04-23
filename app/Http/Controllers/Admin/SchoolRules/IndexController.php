<?php

namespace App\Http\Controllers\Admin\SchoolRules;

use App\Http\Controllers\Controller;
use App\Models\SchoolRule;

class IndexController extends Controller
{
    public function __invoke()
    {
        $rules = SchoolRule::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.school_rules.index', compact('rules'));
    }
}
