<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InternalApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = config('face_match.internal_key');
        $providedKey = $request->header('X-Internal-Key');

        if (! $configuredKey || ! $providedKey || ! hash_equals($configuredKey, $providedKey)) {
            return response()->json(['message' => 'Invalid internal key.'], 401);
        }

        return $next($request);
    }
}
