<?php

namespace App\Http\Middleware;

use App\Services\LoginAttemptLimiter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleLoginAttempts
{
    public function __construct(private LoginAttemptLimiter $limiter) {}

    public function handle(Request $request, Closure $next, string $area): Response
    {
        return $this->limiter->blockedResponse($request, $area) ?? $next($request);
    }
}
