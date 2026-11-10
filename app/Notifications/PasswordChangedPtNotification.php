<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedPtNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Sua senha foi alterada')
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('A senha da sua conta no Point Carreiras foi alterada com sucesso.')
            ->line('Se você não realizou essa alteração, entre em contato com a equipe responsável pelo recrutamento e redefina sua senha imediatamente.')
            ->action('Acessar minha conta', route('candidate.login'))
            ->line('Esta é uma mensagem automática de segurança.');
    }
}
