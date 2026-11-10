<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(public Application $application) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Atualização da entrevista · '.$this->application->vacancy?->title)
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('A entrevista vinculada à vaga '.$this->application->vacancy?->title.' foi cancelada ou será reorganizada pela equipe de recrutamento.')
            ->line('Acompanhe o portal para novas informações e use a central de mensagens se precisar falar com o RH.')
            ->action('Acompanhar candidatura', route('candidate.applications.show', $this->application))
            ->line('Obrigado por acompanhar o processo pelo Point Carreiras.');
    }
}
