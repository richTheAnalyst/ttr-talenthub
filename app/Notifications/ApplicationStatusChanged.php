<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public int $applicationId;
    public string $jobTitle;
    public string $company;
    public string $statusLabel;
    public ?string $note;

    public function __construct(Application $application, ?string $note = null)
    {
        $application->loadMissing('job.employer');

        $this->applicationId = $application->id;
        $this->jobTitle = $application->job->title;
        $this->company = $application->job->employer->company_name;
        $this->statusLabel = $application->status->label();
        $this->note = $note;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Your application for {$this->jobTitle}: {$this->statusLabel}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->company} updated your application for {$this->jobTitle}. It is now: {$this->statusLabel}.");

        if ($this->note) {
            $mail->line("Message from the employer: {$this->note}");
        }

        return $mail->action('Track your application', route('candidate.applications.show', $this->applicationId));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Application {$this->statusLabel}",
            'body' => "{$this->company} moved your application for {$this->jobTitle} to {$this->statusLabel}.",
            'url' => route('candidate.applications.show', $this->applicationId, false),
        ];
    }
}
