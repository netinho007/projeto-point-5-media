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

        if (! $this->shouldTrack($request, $response)) {
            return $response;
        }

        try {
            $sessionId = $request->session()->getId();
            $path = mb_substr('/'.ltrim($request->path(), '/'), 0, 500);
            $visitedOn = now()->toDateString();
            $sessionKey = 'tracked_public_visits.'.sha1($visitedOn.'|'.$path);

            if ($sessionId !== '' && ! $request->session()->has($sessionKey)) {
                $request->session()->put($sessionKey, true);

                SiteVisit::create([
                    // Identificador técnico irreversível da sessão. Não armazenamos IP.
                    'visitor_token' => hash('sha256', $sessionId),
                    'path' => $path,
                    'visited_on' => $visitedOn,
                ]);
            }
        } catch (Throwable $exception) {
            // Analytics nunca deve impedir o site de abrir.
            report($exception);
        }

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (app()->environment(['local', 'testing'])) {
            return false;
        }

        if (! $request->isMethod('GET') || $response->getStatusCode() >= 400) {
            return false;
        }

        $userAgent = (string) $request->userAgent();

        return $userAgent === '' || ! preg_match('/bot|crawler|spider|slurp|bingpreview|facebookexternalhit/i', $userAgent);
    }
}
