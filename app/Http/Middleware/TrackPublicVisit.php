<?php

namespace App\Http\Middleware;

use App\Models\SiteVisit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackPublicVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || $response->getStatusCode() >= 400) {
            return $response;
        }

        $userAgent = (string) $request->userAgent();

        if ($userAgent !== '' && preg_match('/bot|crawler|spider|slurp|bingpreview|facebookexternalhit/i', $userAgent)) {
            return $response;
        }

        try {
            $sessionId = $request->session()->getId();

            if ($sessionId !== '') {
                SiteVisit::create([
                    // Identificador técnico irreversível da sessão. Não armazenamos IP.
                    'visitor_token' => hash('sha256', $sessionId),
                    'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 500),
                    'visited_on' => now()->toDateString(),
                ]);
            }
        } catch (Throwable $exception) {
            // Analytics nunca deve impedir o site de abrir.
            report($exception);
        }

        return $response;
    }
}
