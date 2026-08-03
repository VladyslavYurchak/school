<?php

namespace App\Http\Controllers\Admin\SocialPublishing;

use App\Http\Controllers\Controller;
use App\Models\SocialPublication;
use Illuminate\View\View;

class EditController extends Controller
{
    public function __invoke(SocialPublication $publication): View
    {
        $publication->load('targets');

        return view('admin.social-publishing.edit', compact('publication'));
    }
}
