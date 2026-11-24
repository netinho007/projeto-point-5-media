<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Services\SmsService;
use Illuminate\Console\Command;

class TestPointSms extends Command
{
    protected $signature = 'point:sms-test {phone : Telefone que receberá o teste}';

    protected $description = 'Envia um SMS de teste usando a configuração atual do Twilio';

    public function handle(SmsService $sms): int
    {
        $phone = (string) $this->argument('phone');
        $candidate = new Candidate([
            'phone' => $phone,
        ]);

        try {
            $sent = $sms->send($candidate, 'Point Carreiras: teste de SMS realizado com sucesso.');

            if ($sent) {
                $this->info('SMS de teste enviado com sucesso.');
            } else {
                $this->warn('Modo local: SMS registrado no laravel.log. Configure o Twilio para envio real.');
            }

            $this->line('TWILIO_SMS_FROM atual: '.(config('services.twilio.sms_from') ?: 'vazio'));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Falha ao enviar SMS: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
