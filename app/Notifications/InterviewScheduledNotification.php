<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewScheduledNotification extends Notification
{
    use Queueable;

    public function __construct(public Application $application) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $interview = $this->application->interview;
        $mail = (new MailMessage)
            ->subject('Entrevista agendada · '.($this->application->vacancy?->title ?? 'Point Carreiras'))
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('Sua entrevista foi agendada para '.$interview?->scheduled_at?->format('d/m/Y \à\s H:i').'.')
            ->line('Formato: '.ucfirst((string) ($interview?->format ?? 'não informado')).'.');

        if (filled($interview?->location)) $mail->line('Local: '.$interview->location.'.');
        if (filled($interview?->candidate_instructions)) $mail->line($interview->candidate_instructions);

        return $mail->action('Ver detalhes', route('candidate.applications.show', $this->application));
    }
}
