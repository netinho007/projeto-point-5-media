<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Services\PhoneVerificationService;
use App\Notifications\PasswordChangedPtNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use RuntimeException;

class SecurityController extends Controller
{
    public function index(Request $request)
    {
        return view('candidate.portal.security', ['candidate' => $request->user()->candidate]);
    }

    public function sendPhoneCode(Request $request, PhoneVerificationService $service)
    {
        $candidate = $request->user()->candidate;
        $request->validate(['phone' => ['required','string','max:30']]);
        if ($candidate->phone !== $request->phone) {
            $candidate->update(['phone' => $request->phone, 'phone_verified_at' => null]);
        }
        try {
            $testCode = $service->send($candidate->fresh());
        } catch (RuntimeException $e) {
            return back()->withErrors(['phone' => $e->getMessage()]);
        }
        $message = 'Código de verificação enviado. Ele expira em 10 minutos.';
        if ($testCode) $message .= ' Modo local: código de teste '.$testCode.'.';
        return back()->with('success', $message);
    }

    public function verifyPhone(Request $request, PhoneVerificationService $service)
    {
        $request->validate(['code' => ['required','digits:6']]);
        try {
            $ok = $service->verify($request->user()->candidate, $request->code);
        } catch (RuntimeException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }
        return $ok ? back()->with('success', 'Telefone verificado com sucesso.') : back()->withErrors(['code' => 'Código inválido.']);
    }

    public function preferences(Request $request)
    {
        $data = $request->validate([
            'email' => ['nullable','boolean'], 'portal' => ['nullable','boolean'], 'sms' => ['nullable','boolean'], 'whatsapp' => ['nullable','boolean'],
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
            'current_password' => ['required','current_password'],
            'password' => ['required','confirmed', PasswordRule::min(8)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'A senha atual não confere.',
        ]);

        $request->user()->update(['password' => Hash::make($data['password'])]);

        try {
            $request->user()->notify(new PasswordChangedPtNotification());
        } catch (\Throwable $exception) {
            report($exception);
        }

        return back()->with('success', 'Senha atualizada com sucesso.');
    }
}
