<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BusinessOwnerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && in_array(auth()->user()->role, ['business_owner', 'admin'])) {
            return $next($request);
        }

        return redirect('/')->with('error', 'Access denied. Business owner only.');
    }
}
