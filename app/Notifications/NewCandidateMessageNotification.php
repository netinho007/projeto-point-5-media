<?php

namespace App\Notifications;

use App\Models\CandidateMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCandidateMessageNotification extends Notification
{
    use Queueable;

    public function __construct(public CandidateMessage $message) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->message->subject ?: 'Nova mensagem do recrutamento')
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('Você recebeu uma nova mensagem da equipe de recrutamento.')
            ->line($this->message->message)
            ->action('Abrir mensagens', route('candidate.messages.index'));
    }
}
