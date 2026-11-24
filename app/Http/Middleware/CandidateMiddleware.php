<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CandidateMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->guest(route('candidate.login'));
        }

        if (auth()->user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        if (! auth()->user()->candidate) {
            auth()->user()->candidate()->create([
                'name' => auth()->user()->name,
                'email' => mb_strtolower(auth()->user()->email),
                'privacy_consent_at' => now(),
                'contact_preferences' => ['email' => true, 'portal' => true, 'sms' => false, 'whatsapp' => false],
            ]);
        }

        $candidate = auth()->user()->candidate;

        if ($candidate->account_active === false) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('candidate.login')->withErrors(['email' => 'Esta conta está desativada.']);
        }

        if ($this->needsContactVerification($request)) {
            return redirect()->route('candidate.security')
                ->with('warning', 'Confirme seu e-mail para liberar o portal.');
        }

        return $next($request);
    }

    private function needsContactVerification(Request $request): bool
    {
        $user = auth()->user();
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $allowedRoutes = [
            'candidate.security',
            'candidate.phone.send',
            'candidate.phone.verify',
            'candidate.email.change',
            'candidate.verification.send',
            'candidate.logout',
            'verification.verify',
        ];

        return ! in_array($request->route()?->getName(), $allowedRoutes, true);
    }
}
