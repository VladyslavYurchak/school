<?php

namespace App\Http\Controllers;

use App\Models\SchoolRule;

class SchoolRulePageController extends Controller
{
    public function index()
    {
        $rules = SchoolRule::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('index.rules.index', compact('rules'));
    }
}


