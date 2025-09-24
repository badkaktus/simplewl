<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectWww
{
    public function handle(Request $request, Closure $next): Response
    {
        if (str_starts_with($request->getHost(), 'www.')) {
            $host = substr($request->getHost(), 4);
            $url = $request->getScheme() . '://' . $host . $request->getRequestUri();

            return redirect($url, 301);
        }

        return $next($request);
    }
}
