<?php

namespace App\Http\Controllers\Admin\SocialPublishing;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CreateController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.social-publishing.create');
    }
}
