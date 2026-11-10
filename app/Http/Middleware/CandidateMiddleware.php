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

        if (! auth()->user()->candidate->account_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('candidate.login')->withErrors(['email' => 'Esta conta está desativada.']);
        }

        return $next($request);
    }
}
