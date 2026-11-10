<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('candidate.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([
            'email' => mb_strtolower($credentials['email']),
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'E-mail ou senha incorretos.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        if (Auth::user()->is_admin) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'Use o acesso administrativo para esta conta.']);
        }

        if (! Auth::user()->candidate?->account_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->withErrors(['email' => 'Esta conta está desativada. Entre em contato com o recrutamento para reativá-la.']);
        }

        return redirect()->intended(route('candidate.portal'));
    }

    public function showRegister()
    {
        return view('candidate.auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
            'privacy_consent' => ['accepted'],
            'talent_pool' => ['nullable', 'boolean'],
        ], [
            'privacy_consent.accepted' => 'Você precisa aceitar a Política de Privacidade para criar sua conta.',
        ]);

        $email = mb_strtolower($validated['email']);
        $user = DB::transaction(function () use ($validated, $email, $request) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $email,
                'password' => Hash::make($validated['password']),
            ]);

            $candidate = Candidate::where('email', $email)->whereNull('user_id')->first();

            if ($candidate) {
                $candidate->update([
                    'user_id' => $user->id,
                    'name' => $validated['name'],
                    'privacy_consent_at' => $candidate->privacy_consent_at ?: now(),
                    'talent_pool' => $request->boolean('talent_pool'),
                    'talent_pool_consent_at' => $request->boolean('talent_pool') ? now() : null,
                    'talent_pool_expires_at' => $request->boolean('talent_pool') ? now()->addMonths(12) : null,
                    'contact_preferences' => ['email' => true, 'portal' => true, 'sms' => false, 'whatsapp' => false],
                ]);
            } else {
                $user->candidate()->create([
                    'name' => $validated['name'],
                    'email' => $email,
                    'privacy_consent_at' => now(),
                    'talent_pool' => $request->boolean('talent_pool'),
                    'talent_pool_consent_at' => $request->boolean('talent_pool') ? now() : null,
                    'talent_pool_expires_at' => $request->boolean('talent_pool') ? now()->addMonths(12) : null,
                    'contact_preferences' => ['email' => true, 'portal' => true, 'sms' => false, 'whatsapp' => false],
                ]);
            }

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('candidate.portal'))
            ->with('success', 'Conta criada. Complete seu perfil para aproveitar melhor as oportunidades.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('vacancies.index');
    }

    public function showForgotPassword()
    {
        return view('candidate.auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        $status = Password::sendResetLink(['email' => mb_strtolower($request->email)]);

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('candidate.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $status = Password::reset($validated, function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('candidate.login')->with('success', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
