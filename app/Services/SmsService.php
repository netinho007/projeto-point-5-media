<?php

namespace App\Services;

use App\Models\Candidate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SmsService
{
    public function send(Candidate $candidate, string $message): bool
    {
        if (! filled($candidate->phone)) {
            throw new RuntimeException('Informe um telefone antes de enviar SMS.');
        }

        $to = $this->normalizePhone($candidate->phone);
        $configurationIssue = $this->configurationIssue();

        if ($configurationIssue) {
            throw new RuntimeException($configurationIssue);
        }

        if ($this->configured()) {
            $response = Http::timeout(10)
                ->asForm()
                ->withBasicAuth(config('services.twilio.sid'), config('services.twilio.token'))
                ->post('https://api.twilio.com/2010-04-01/Accounts/'.config('services.twilio.sid').'/Messages.json', [
                    'From' => config('services.twilio.sms_from'),
                    'To' => $to,
                    'Body' => $message,
                ]);

            if (! $response->successful()) {
                Log::warning('Falha ao enviar SMS pelo Twilio Messaging API.', [
                    'candidate_id' => $candidate->id,
                    'to' => $to,
                    'status' => $response->status(),
                    'code' => $response->json('code'),
                    'message' => $response->json('message'),
                ]);

                if ((int) $response->json('code') === 572006) {
                    throw new RuntimeException('Sua conta Twilio trial bloqueou SMS com texto livre. Use um Verify Service SID válido, que começa com VA, ou configure templates/libere a conta na Twilio.');
                }

                throw new RuntimeException('Não foi possível enviar o SMS. Verifique a configuração do Twilio no laravel.log.');
            }

            return true;
        }

        Log::info('SMS local', [
            'candidate_id' => $candidate->id,
            'to' => $to,
            'message' => $message,
        ]);

        return false;
    }

    public function configured(): bool
    {
        return ! $this->configurationIssue()
            && filled(config('services.twilio.sid'))
            && filled(config('services.twilio.token'))
            && filled(config('services.twilio.sms_from'));
    }

    public function configurationIssue(): ?string
    {
        $sid = (string) config('services.twilio.sid');
        $token = (string) config('services.twilio.token');
        $from = (string) config('services.twilio.sms_from');

        if (! filled($sid) && ! filled($token) && ! filled($from)) {
            return null;
        }

        if (! filled($sid) || ! filled($token) || ! filled($from)) {
            return 'Preencha TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN e TWILIO_SMS_FROM para enviar SMS.';
        }

        if (! str_starts_with($from, '+')) {
            return 'TWILIO_SMS_FROM precisa ser um número Twilio no formato internacional, por exemplo +5511999999999. Não use seu telefone pessoal como remetente.';
        }

        return null;
    }

    public function normalizePhone(string $phone): string
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
}
