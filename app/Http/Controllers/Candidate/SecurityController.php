<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Notifications\PasswordChangedPtNotification;
use App\Services\PhoneVerificationService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use RuntimeException;

class SecurityController extends Controller
{
    public function index(Request $request, PhoneVerificationService $phoneVerification)
    {
        return view('candidate.portal.security', [
            'candidate' => $request->user()->candidate,
            'phoneVerificationAvailable' => $phoneVerification->canSend(),
        ]);
    }

    public function sendPhoneCode(Request $request, PhoneVerificationService $service)
    {
        $candidate = $request->user()->candidate;
        $request->validate([
            'phone' => [
                'required',
                'string',
                'max:30',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $digits = preg_replace('/\D+/', '', (string) $value) ?: '';

                    if (! in_array(strlen($digits), [10, 11, 12, 13], true)) {
                        $fail('Informe um telefone válido com DDD.');
                    }
                },
            ],
        ]);

        if ($candidate->phone !== $request->phone) {
            $candidate->update(['phone' => $request->phone, 'phone_verified_at' => null]);
        }
        try {
            $testCode = $service->send($candidate->fresh());
        } catch (RuntimeException $e) {
            return back()->withErrors(['phone' => $e->getMessage()]);
        }
        $message = 'Código de verificação enviado. Ele expira em 10 minutos.';
        if ($testCode) {
            $message .= ' Modo local: código de teste '.$testCode.'.';
        }

        return back()->with('success', $message);
    }

    public function verifyPhone(Request $request, PhoneVerificationService $service)
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        try {
            $ok = $service->verify($request->user()->candidate, $request->code);
        } catch (RuntimeException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return $ok ? back()->with('success', 'Telefone verificado com sucesso.') : back()->withErrors(['code' => 'Código inválido.']);
    }

    public function updateEmail(Request $request, SmsService $sms)
    {
        $user = $request->user();
        $candidate = $user->candidate;
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
                Rule::unique('candidates', 'email')->ignore($candidate->id),
            ],
            'current_password' => ['required', 'current_password'],
        ], [
            'current_password.current_password' => 'A senha atual não confere.',
        ]);

        $newEmail = mb_strtolower($validated['email']);
        if ($newEmail === $user->email) {
            return back()->with('success', 'Esse e-mail já está na sua conta.');
        }

        $oldEmail = $user->email;

        DB::transaction(function () use ($user, $candidate, $newEmail): void {
            $user->forceFill([
                'email' => $newEmail,
                'email_verified_at' => null,
            ])->save();

            $candidate->update(['email' => $newEmail]);
        });

        $user->refresh();
        $candidate = $candidate->fresh();
        $message = 'E-mail atualizado. Enviamos um link de verificação para o novo endereço.';
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);
            $message = 'E-mail atualizado, mas não conseguimos enviar o link de verificação agora. Autorize o IP na Brevo e reenvie pela tela de Segurança.';
        }

        if ($candidate->phone_verified_at) {
            try {
                $sent = $sms->send(
                    $candidate,
                    'Point Carreiras: o e-mail da sua conta foi alterado de '.$oldEmail.' para '.$newEmail.'. Se não foi você, redefina sua senha imediatamente.'
                );

                if (! $sent && app()->environment(['local', 'testing'])) {
                    $message .= ' Modo local: aviso por SMS registrado no laravel.log.';
                }
            } catch (RuntimeException $e) {
                report($e);
                $message .= ' Não conseguimos enviar o aviso por SMS; confira a configuração do Twilio.';
            }
        } else {
            $message .= ' Verifique seu telefone para receber avisos por SMS nas próximas alterações.';
        }

        return back()->with('success', $message);
    }

    public function preferences(Request $request)
    {
        $data = $request->validate([
            'email' => ['nullable', 'boolean'], 'portal' => ['nullable', 'boolean'], 'sms' => ['nullable', 'boolean'], 'whatsapp' => ['nullable', 'boolean'],
        ]);
        $request->user()->candidate->update(['contact_preferences' => [
            'email' => $request->boolean('email'), 'portal' => $request->boolean('portal'), 'sms' => $request->boolean('sms'), 'whatsapp' => $request->boolean('whatsapp'),
        ]]);

        return back()->with('success', 'Preferências de comunicação atualizadas.');
    }

    public function deactivate(Request $request)
    {
        $request->validate(['confirm' => ['accepted']]);
        $request->user()->candidate->update(['account_active' => false]);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('vacancies.index')->with('success', 'Conta desativada. Seus dados foram preservados conforme as regras de privacidade.');
    }

    public function requestDeletion(Request $request)
    {
        $request->validate(['confirm_delete' => ['accepted']]);
        $request->user()->candidate->update(['deletion_requested_at' => now(), 'talent_pool' => false, 'talent_pool_consent_at' => null, 'talent_pool_expires_at' => null]);

        return back()->with('success', 'Solicitação de exclusão registrada.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'A senha atual não confere.',
        ]);

        $request->user()->update(['password' => Hash::make($data['password'])]);

        try {
            $request->user()->notify(new PasswordChangedPtNotification);
        } catch (\Throwable $exception) {
            report($exception);
        }

        return back()->with('success', 'Senha atualizada com sucesso.');
    }
}
