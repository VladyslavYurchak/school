<?php

namespace App\Http\Controllers\Admin\SocialPublishing;

use App\Http\Controllers\Controller;
use App\Models\SocialPublication;
use Illuminate\View\View;

class IndexController extends Controller
{
    public function __invoke(): View
    {
        $publications = SocialPublication::query()
            ->with(['targets', 'author'])
            ->latest()
            ->paginate(12);

        return view('admin.social-publishing.index', compact('publications'));
    }
}
