<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(public Application $application, public ?string $publicNote = null) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Atualização da sua candidatura · '.($this->application->vacancy?->title ?? 'Point Carreiras'))
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('Sua candidatura agora está na etapa: '.$this->application->status_label.'.');

        if (filled($this->publicNote)) {
            $mail->line($this->publicNote);
        }

        return $mail
            ->action('Ver andamento', route('candidate.applications.show', $this->application))
            ->line('As decisões do processo são realizadas pela equipe de recrutamento.');
    }
}
