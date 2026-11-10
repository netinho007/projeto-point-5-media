<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestPointMail extends Command
{
    protected $signature = 'point:mail-test {email : Endereço que receberá o teste}';
    protected $description = 'Envia um e-mail de teste usando a configuração atual do Laravel';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        try {
            Mail::raw(
                'Este é um teste de envio do Point Carreiras. Se você recebeu esta mensagem, a configuração de e-mail está funcionando.',
                fn ($message) => $message->to($email)->subject('Teste de e-mail · Point Carreiras')
            );
            $this->info('E-mail de teste processado com sucesso.');
            $this->line('MAIL_MAILER atual: '.config('mail.default'));
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Falha ao enviar: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
