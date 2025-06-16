<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DialogflowSecret
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedSecret = config('services.dialogflow.secret'); // Assuming you'll configure this in config/services.php
        $incoming = $request->bearerToken();

        // Fallback to query parameter for local testing if header is not available
        if (!$incoming) {
            $requestSecret = $request->query('secret');
        }

        if (!$expectedSecret || $incoming !== $expectedSecret) {
            return response('Unauthorized', 401);
        }

        return $next($request);
    }
}
