<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class LoginAttemptLimiter
{
    private const ACCOUNT_ATTEMPTS = 5;

    private const IP_ATTEMPTS = 20;

    private const DECAY_SECONDS = 60;

    public function blockedResponse(Request $request, string $area): ?Response
    {
        $keys = $this->keys($request, $area);
        $retryAfter = 0;
        foreach ([$keys['account'] => self::ACCOUNT_ATTEMPTS, $keys['ip'] => self::IP_ATTEMPTS] as $key => $limit) {
            if (RateLimiter::tooManyAttempts($key, $limit)) {
                $retryAfter = max($retryAfter, RateLimiter::availableIn($key));
            }
        }
        if ($retryAfter === 0) {
            return null;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Muitas tentativas de login. Tente novamente em {$retryAfter} segundos.",
                'retry_after' => $retryAfter,
            ], 429)->header('Retry-After', (string) $retryAfter);
        }

        return redirect()->route($area.'.login')
            ->withErrors(['email' => 'Muitas tentativas de login. Aguarde o prazo abaixo para tentar novamente.'])
            ->withInput($request->only('email'))
            ->with('login_retry_at', now()->timestamp + $retryAfter)
            ->header('Retry-After', (string) $retryAfter);
    }

    public function recordFailure(Request $request, string $area): void
    {
        foreach ($this->keys($request, $area) as $key) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
        }
    }

    public function clearAccount(Request $request, string $area): void
    {
        // Um login válido não pode apagar o limite geral contra tentativas em várias contas.
        RateLimiter::clear($this->keys($request, $area)['account']);
        $request->session()->forget('login_retry_at');
    }

    private function keys(Request $request, string $area): array
    {
        $ip = (string) $request->ip();
        $email = $request->input('email');
        $email = is_string($email) ? mb_strtolower(trim($email)) : '';

        return [
            'account' => 'point-login:'.hash('sha256', $area.'|'.$ip.'|'.$email),
            'ip' => 'point-login-ip:'.hash('sha256', $area.'|'.$ip),
        ];
    }
}
