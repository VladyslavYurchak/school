<?php

namespace App\Http\Controllers\Admin\SubscriptionTemplate;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionTemplate;

class IndexController extends Controller
{
    public function __invoke()
    {
        $individualTemplates = SubscriptionTemplate::where('type', 'individual')->orderBy('title')->get();
        $groupTemplates = SubscriptionTemplate::where('type', 'group')->orderBy('title')->get();
        $pairTemplates = SubscriptionTemplate::where('type', 'pair')->orderBy('title')->get();


        return view('admin.subscription_templates.index', compact('individualTemplates', 'groupTemplates', 'pairTemplates'));
    }
}
