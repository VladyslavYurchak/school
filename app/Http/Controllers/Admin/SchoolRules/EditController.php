<?php

namespace App\Http\Controllers\Admin\SchoolRules;

use App\Http\Controllers\Controller;
use App\Models\SchoolRule;

class EditController extends Controller
{
    public function __invoke(SchoolRule $schoolRule)
    {
        return view('admin.school_rules.edit', compact('schoolRule'));
    }
}
