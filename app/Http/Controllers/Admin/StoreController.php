<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;


class StoreController extends Controller
{

    public function __invoke()
    {
        return view('admin.index');
    }
}
