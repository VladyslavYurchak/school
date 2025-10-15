<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class IsTeacher
{
    public function handle($request, Closure $next)
    {
        

        if (Auth::check() && (Auth::user()->isTeacher() || Auth::user()->isAdmin())) {
            return $next($request);
        }

        abort(403, 'Доступ заборонено');
    }
}
