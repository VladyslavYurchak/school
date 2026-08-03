<?php

namespace App\Http\Middleware;

use App\Support\Seo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetRobotsHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $robots = Seo::robotsFor($request);

        if ($robots === Seo::PRIVATE_ROBOTS) {
            $response->headers->set('X-Robots-Tag', $robots);
        }

        return $response;
    }
}
