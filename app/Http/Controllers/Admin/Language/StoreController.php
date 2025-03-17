<?php

namespace App\Http\Controllers\Admin\Language;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __invoke (Request $request)
    {
        $request->validate(['name' => 'required|string|unique:languages,name']);

        Language::create(['name' => $request->name]);

        return redirect()->back()->with('success', 'Мову успішно додано!');
    }
}
