<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Automated acknowledgement sent to a candidate right after they upload a CV. */
class CvSubmitted extends Notification
{
    public const DEFAULT_SUBJECT = 'We received your CV';
    public const DEFAULT_MESSAGE = "Hi {name},\n\nThank you for submitting your CV to TTR TalentHub. It has been received and will be put into consideration. We will contact you if there is a suitable opportunity.";

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    private function body(object $notifiable): string
    {
        return str_replace('{name}', $notifiable->name, Setting::read('cv_ack_message', self::DEFAULT_MESSAGE));
    }

    private function subject(): string
    {
        return Setting::read('cv_ack_subject', self::DEFAULT_SUBJECT);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->subject());

        foreach (preg_split('/\R{2,}/', trim($this->body($notifiable))) as $paragraph) {
            $mail->line($paragraph);
        }

        return $mail->action('View your CVs', route('candidate.cvs.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->subject(),
            'body' => $this->body($notifiable),
            'url' => route('candidate.cvs.index', [], false),
        ];
    }
}