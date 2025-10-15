<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrimTrailingSlash
{
    public function handle(Request $request, Closure $next)
    {
        $path = $request->getPathInfo();

        if ($path !== '/' && str_ends_with($path, '/')) {
            $new = rtrim($path, '/');
            return redirect($new, 301);
        }

        return $next($request);
    }
}
