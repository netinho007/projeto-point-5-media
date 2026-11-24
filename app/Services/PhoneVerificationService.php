<?php

namespace App\Services;

use App\Models\Candidate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PhoneVerificationService
{
    public function __construct(private readonly SmsService $sms) {}

    public function canSend(): bool
    {
        $hasTwilioConfig = filled(config('services.twilio.sid'))
            || filled(config('services.twilio.token'))
            || filled(config('services.twilio.verify_sid'))
            || filled(config('services.twilio.sms_from'));

        if ($hasTwilioConfig) {
            return $this->twilioVerifyConfigured() || $this->sms->configured();
        }

        return $this->twilioVerifyConfigured()
            || $this->sms->configured()
            || app()->environment(['local', 'testing']);
    }

    public function send(Candidate $candidate): ?string
    {
        if (! filled($candidate->phone)) {
            throw new RuntimeException('Informe um telefone antes de solicitar a verificação.');
        }

        $verifyIssue = $this->twilioVerifyConfigurationIssue();
        if ($verifyIssue && ! $this->sms->configured()) {
            $smsIssue = $this->sms->configurationIssue();

            throw new RuntimeException($verifyIssue.($smsIssue ? ' '.$smsIssue : ''));
        }

        if ($this->twilioVerifyConfigured()) {
            $response = Http::timeout(10)
                ->asForm()
                ->withBasicAuth(config('services.twilio.sid'), config('services.twilio.token'))
                ->post('https://verify.twilio.com/v2/Services/'.config('services.twilio.verify_sid').'/Verifications', [
                    'To' => $this->normalizePhone($candidate->phone),
                    'Channel' => 'sms',
                ]);

            if ($response->successful()) {
                $candidate->update([
                    'phone_verification_hash' => null,
                    'phone_verification_expires_at' => now()->addMinutes(10),
                    'phone_verification_attempts' => 0,
                ]);

                return null;
            }

            Log::warning('Falha no Twilio Verify; tentando SMS normal se disponível.', [
                'candidate_id' => $candidate->id,
                'status' => $response->status(),
                'code' => $response->json('code'),
                'message' => $response->json('message'),
            ]);

            if (! $this->sms->configured()) {
                throw new RuntimeException('Não foi possível enviar o código por SMS. Confira o Verify Service SID no Twilio ou configure TWILIO_SMS_FROM.');
            }
        } elseif ($verifyIssue) {
            Log::warning('Twilio Verify configurado com valor inválido; tentando SMS normal se disponível.', [
                'candidate_id' => $candidate->id,
                'issue' => $verifyIssue,
            ]);
        }

        if (! $this->sms->configured() && ! app()->environment(['local', 'testing'])) {
            throw new RuntimeException($this->sms->configurationIssue() ?: 'Configure o Twilio para enviar o código por SMS.');
        }

        $code = (string) random_int(100000, 999999);
        $candidate->update([
            'phone_verification_hash' => Hash::make($code),
            'phone_verification_expires_at' => now()->addMinutes(10),
            'phone_verification_attempts' => 0,
        ]);

        $sent = false;
        if ($this->sms->configured()) {
            $sent = $this->sms->send(
                $candidate,
                'Point Carreiras: seu código de verificação é '.$code.'. Ele expira em 10 minutos.'
            );
        }

        if (! $sent) {
            Log::info('Código local de verificação de telefone', ['candidate_id' => $candidate->id, 'code' => $code]);
        }

        return $sent ? null : $code;
    }

    public function verify(Candidate $candidate, string $code): bool
    {
        if (! $candidate->phone_verification_expires_at) {
            throw new RuntimeException('Solicite um código de verificação antes de continuar.');
        }

        if ($candidate->phone_verification_expires_at->isPast()) {
            throw new RuntimeException('O código expirou. Solicite um novo.');
        }

        if ($candidate->phone_verification_attempts >= 5) {
            throw new RuntimeException('Limite de tentativas atingido. Solicite um novo código.');
        }

        $candidate->increment('phone_verification_attempts');

        if (filled($candidate->phone_verification_hash)) {
            $approved = Hash::check($code, $candidate->phone_verification_hash);
        } elseif ($this->twilioVerifyConfigured()) {
            $response = Http::timeout(10)
                ->asForm()
                ->withBasicAuth(config('services.twilio.sid'), config('services.twilio.token'))
                ->post('https://verify.twilio.com/v2/Services/'.config('services.twilio.verify_sid').'/VerificationCheck', [
                    'To' => $this->normalizePhone($candidate->phone),
                    'Code' => $code,
                ]);
            $approved = $response->successful() && $response->json('status') === 'approved';
        } else {
            $approved = false;
        }

        if ($approved) {
            $candidate->update([
                'phone_verified_at' => now(),
                'phone_verification_hash' => null,
                'phone_verification_expires_at' => null,
                'phone_verification_attempts' => 0,
            ]);
        }

        return $approved;
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if (! in_array(strlen($digits), [10, 11, 12, 13], true)) {
            throw new RuntimeException('Informe um telefone válido com DDD.');
        }

        if (str_starts_with($digits, '55')) {
            return '+'.$digits;
        }

        return '+55'.$digits;
    }

    private function twilioVerifyConfigured(): bool
    {
        return filled(config('services.twilio.verify_sid'))
            && filled(config('services.twilio.sid'))
            && filled(config('services.twilio.token'))
            && ! $this->twilioVerifyConfigurationIssue();
    }

    private function twilioVerifyConfigurationIssue(): ?string
    {
        $sid = (string) config('services.twilio.sid');
        $token = (string) config('services.twilio.token');
        $verifySid = (string) config('services.twilio.verify_sid');

        if (! filled($sid) && ! filled($token) && ! filled($verifySid)) {
            return null;
        }

        if (! filled($sid) || ! filled($token) || ! filled($verifySid)) {
            return 'Preencha TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN e TWILIO_VERIFY_SERVICE_SID para enviar códigos por telefone.';
        }

        if (! str_starts_with($verifySid, 'VA')) {
            return 'TWILIO_VERIFY_SERVICE_SID precisa ser o Verify Service SID da Twilio, que começa com VA. O valor atual parece ser outro tipo de chave.';
        }

        return null;
    }
}
