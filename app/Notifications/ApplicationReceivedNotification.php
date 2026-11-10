<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationReceivedNotification extends Notification
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
            ->subject('Candidatura recebida · '.($this->application->vacancy?->title ?? 'Point Carreiras'))
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('Recebemos sua candidatura para '.($this->application->vacancy?->title ?? 'a oportunidade').'.')
            ->line('Você pode acompanhar cada atualização pelo seu portal.')
            ->action('Acompanhar candidatura', route('candidate.applications.show', $this->application))
            ->line('Obrigado por participar do nosso processo seletivo.');
    }
}
