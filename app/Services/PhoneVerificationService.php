<?php

namespace App\Services;

use App\Models\Candidate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PhoneVerificationService
{
    public function send(Candidate $candidate): ?string
    {
        if (! filled($candidate->phone)) {
            throw new RuntimeException('Informe um telefone antes de solicitar a verificação.');
        }

        if (config('services.twilio.verify_sid') && config('services.twilio.sid') && config('services.twilio.token')) {
            $response = Http::asForm()
                ->withBasicAuth(config('services.twilio.sid'), config('services.twilio.token'))
                ->post('https://verify.twilio.com/v2/Services/'.config('services.twilio.verify_sid').'/Verifications', [
                    'To' => $this->normalizePhone($candidate->phone),
                    'Channel' => 'sms',
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('Não foi possível enviar o código por SMS. Verifique a configuração do Twilio.');
            }

            $candidate->update([
                'phone_verification_hash' => null,
                'phone_verification_expires_at' => now()->addMinutes(10),
                'phone_verification_attempts' => 0,
            ]);

            return null;
        }

        $code = (string) random_int(100000, 999999);
        $candidate->update([
            'phone_verification_hash' => Hash::make($code),
            'phone_verification_expires_at' => now()->addMinutes(10),
            'phone_verification_attempts' => 0,
        ]);
        Log::info('Código local de verificação de telefone', ['candidate_id' => $candidate->id, 'code' => $code]);

        return app()->environment('local') ? $code : null;
    }

    public function verify(Candidate $candidate, string $code): bool
    {
        if ($candidate->phone_verification_attempts >= 5) {
            throw new RuntimeException('Limite de tentativas atingido. Solicite um novo código.');
        }

        $candidate->increment('phone_verification_attempts');

        if ($candidate->phone_verification_expires_at?->isPast()) {
            throw new RuntimeException('O código expirou. Solicite um novo.');
        }

        if (config('services.twilio.verify_sid') && config('services.twilio.sid') && config('services.twilio.token')) {
            $response = Http::asForm()
                ->withBasicAuth(config('services.twilio.sid'), config('services.twilio.token'))
                ->post('https://verify.twilio.com/v2/Services/'.config('services.twilio.verify_sid').'/VerificationCheck', [
                    'To' => $this->normalizePhone($candidate->phone),
                    'Code' => $code,
                ]);
            $approved = $response->successful() && $response->json('status') === 'approved';
        } else {
            $approved = filled($candidate->phone_verification_hash) && Hash::check($code, $candidate->phone_verification_hash);
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
        if (str_starts_with($digits, '55')) return '+'.$digits;
        return '+55'.$digits;
    }
}
