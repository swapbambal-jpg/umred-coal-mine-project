<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckSession
{
    public function handle($request, Closure $next)
    {
        if (!Auth::check()) { // Check if user is not logged in
            return redirect('/login')->with('error', 'Session expired. Please log in again.');
        }
        return $next($request);
    }
}
