<?php

namespace App\Http\Controllers\Admin\SubscriptionTemplate;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:individual,group',
            'lessons_per_week' => 'required|integer|min:1|max:7',
            'price' => 'required|numeric|min:0',
        ]);

        SubscriptionTemplate::create($validated);

        $individualTemplates = SubscriptionTemplate::where('type', 'individual')->orderBy('title')->get();
        $groupTemplates = SubscriptionTemplate::where('type', 'group')->orderBy('title')->get();

        return redirect()
            ->route('admin.subscription-templates.index')
            ->with('success', 'Шаблон абонементу успішно створено.');
    }
}
