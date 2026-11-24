<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\User;
use App\Services\LoginAttemptLimiter;
use App\Services\PhoneVerificationService;
use App\Services\SmsService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use RuntimeException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('candidate.auth.login');
    }

    public function login(Request $request, LoginAttemptLimiter $limiter)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([
            'email' => mb_strtolower($credentials['email']),
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            $limiter->recordFailure($request, 'candidate');

            return back()->withErrors(['email' => 'E-mail ou senha incorretos.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        if (Auth::user()->is_admin) {
            $limiter->recordFailure($request, 'candidate');
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'Use o acesso administrativo para esta conta.']);
        }

        $user = Auth::user();
        $candidate = $user->candidate;

        if ($candidate && $candidate->account_active === false) {
            $limiter->recordFailure($request, 'candidate');
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'Esta conta está desativada. Entre em contato com o recrutamento para reativá-la.']);
        }

        $limiter->clearAccount($request, 'candidate');
        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('candidate.security')
                ->with('warning', 'Confirme seu e-mail para liberar o portal.');
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
            'phone' => [
                'nullable',
                'string',
                'max:30',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! filled($value)) {
                        return;
                    }

                    $digits = preg_replace('/\D+/', '', (string) $value) ?: '';

                    if (! in_array(strlen($digits), [10, 11, 12, 13], true)) {
                        $fail('Informe um telefone válido com DDD.');
                    }
                },
            ],
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
            $candidateData = [
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'phone_verified_at' => null,
                'phone_verification_hash' => null,
                'phone_verification_expires_at' => null,
                'phone_verification_attempts' => 0,
                'privacy_consent_at' => now(),
                'talent_pool' => $request->boolean('talent_pool'),
                'talent_pool_consent_at' => $request->boolean('talent_pool') ? now() : null,
                'talent_pool_expires_at' => $request->boolean('talent_pool') ? now()->addMonths(12) : null,
                'contact_preferences' => ['email' => true, 'portal' => true, 'sms' => false, 'whatsapp' => false],
            ];

            if ($candidate) {
                $candidate->update(array_merge($candidateData, [
                    'user_id' => $user->id,
                    'privacy_consent_at' => $candidate->privacy_consent_at ?: now(),
                ]));
            } else {
                $user->candidate()->create(array_merge($candidateData, [
                    'email' => $email,
                ]));
            }

            return $user;
        });

        $emailMessage = 'Confirme seu e-mail pelo link enviado';
        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            report($e);
            $emailMessage = 'Não conseguimos enviar o e-mail de verificação agora. Autorize o IP na Brevo e reenvie pela tela de Segurança';
        }
        Auth::login($user);
        $request->session()->regenerate();

        $message = 'Conta criada. '.$emailMessage.'. O telefone pode ser confirmado depois, sem bloquear seu acesso.';

        return redirect()->route('candidate.security')->with('success', $message);
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

    public function showPhoneRecovery()
    {
        return view('candidate.auth.forgot-password-phone');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        try {
            $status = Password::sendResetLink(['email' => mb_strtolower($request->email)]);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Não conseguimos enviar o e-mail agora. Autorize o IP na Brevo ou confira a configuração SMTP.']);
        }

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function sendPhoneResetLink(Request $request, SmsService $sms, PhoneVerificationService $phoneVerification)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $candidate = $this->findCandidateByVerifiedPhone($validated['phone'], $sms);
        $localCode = null;
        $request->session()->put('phone_password_reset', [
            'candidate_id' => $candidate?->id,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        if ($candidate?->user) {
            try {
                $localCode = $phoneVerification->send($candidate);
            } catch (RuntimeException $e) {
                $request->session()->forget('phone_password_reset');

                return back()->withErrors(['phone' => $e->getMessage()])->onlyInput('phone');
            }
        }

        $message = 'Se existir uma conta com esse telefone verificado, enviaremos um código de recuperação por SMS. Digite o código abaixo para criar uma nova senha.';
        if ($localCode) {
            $message .= ' Modo local: código de recuperação '.$localCode.'.';
        }

        return back()->with('success', $message)->withInput($request->only('phone'));
    }

    public function resetPasswordByPhone(Request $request, PhoneVerificationService $phoneVerification)
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $payload = $request->session()->get('phone_password_reset');
        if (! $payload || now()->timestamp > (int) ($payload['expires_at'] ?? 0)) {
            $request->session()->forget('phone_password_reset');

            return back()->withErrors(['code' => 'Solicite um novo código de recuperação.'])->withInput();
        }

        $candidate = Candidate::with('user')->find($payload['candidate_id'] ?? null);
        if (! $candidate?->user || ! $candidate->phone_verified_at) {
            return back()->withErrors(['code' => 'Código inválido ou expirado.'])->withInput();
        }

        try {
            $ok = $phoneVerification->verify($candidate, $validated['code']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['code' => $e->getMessage()])->withInput();
        }

        if (! $ok) {
            return back()->withErrors(['code' => 'Código inválido.'])->withInput();
        }

        $candidate->user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        $request->session()->forget('phone_password_reset');

        return redirect()->route('candidate.login')->with('success', 'Senha redefinida com sucesso. Entre com a nova senha.');
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

    private function findCandidateByVerifiedPhone(string $phone, SmsService $sms): ?Candidate
    {
        try {
            $normalized = $sms->normalizePhone($phone);
        } catch (RuntimeException) {
            return null;
        }

        return Candidate::query()
            ->whereNotNull('user_id')
            ->whereNotNull('phone')
            ->whereNotNull('phone_verified_at')
            ->with('user')
            ->get()
            ->first(function (Candidate $candidate) use ($normalized, $sms): bool {
                try {
                    return $sms->normalizePhone($candidate->phone) === $normalized;
                } catch (RuntimeException) {
                    return false;
                }
            });
    }
}
