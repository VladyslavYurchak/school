<?php

namespace App\Support;

use Illuminate\Http\Request;

class Seo
{
    public const PUBLIC_ROBOTS = 'index, follow, max-image-preview:large';

    public const PRIVATE_ROBOTS = 'noindex, nofollow, noarchive';

    public static function robotsFor(Request $request): string
    {
        foreach (config('seo.noindex_paths', []) as $pattern) {
            if ($request->is($pattern)) {
                return self::PRIVATE_ROBOTS;
            }
        }

        return self::PUBLIC_ROBOTS;
    }
}
