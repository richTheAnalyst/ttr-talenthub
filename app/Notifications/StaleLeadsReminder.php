<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to admins when new leads have sat untouched past the follow-up window. */
class StaleLeadsReminder extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param  list<string>  $top  names of the highest-scoring untouched leads */
    public function __construct(public int $count, public int $hours, public array $top = [])
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("{$this->count} leads need a first contact")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->count} leads have been waiting more than {$this->hours} hours without contact.");

        if ($this->top) {
            $mail->line('Highest scoring: '.implode(', ', $this->top).'.');
        }

        return $mail->action('Open the lead list', route('admin.leads.index', ['stage' => 'new']));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Leads waiting for contact',
            'body' => "{$this->count} leads have had no contact for over {$this->hours} hours.",
            'url' => route('admin.leads.index', ['stage' => 'new'], false),
        ];
    }
}
