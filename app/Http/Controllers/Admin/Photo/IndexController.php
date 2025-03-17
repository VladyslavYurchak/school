<?php

namespace App\Http\Controllers\Admin\Photo;

use App\Http\Controllers\Controller;
use App\Models\Photo;

class IndexController extends Controller
{
    public function __invoke()
    {
            $photos = Photo::all();
            return view('admin.photo.index', compact('photos')); // ОНОВЛЕНО: 'photo.index' замість 'photos.index'
    }
}
