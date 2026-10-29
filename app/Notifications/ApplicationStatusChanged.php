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
    public string $statusValue;
    public ?string $note;

    public function __construct(Application $application, ?string $note = null)
    {
        $application->loadMissing('job.employer');

        $this->applicationId = $application->id;
        $this->jobTitle = $application->job->title;
        $this->company = $application->job->employer->company_name;
        $this->statusLabel = $application->status->label();
        $this->statusValue = $application->status->value;
        $this->note = $note;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    private function headline(): string
    {
        return match ($this->statusValue) {
            'accepted' => 'Your application was accepted',
            'hired' => 'Congratulations, you got the job!',
            default => "Application {$this->statusLabel}",
        };
    }

    private function message(): string
    {
        return match ($this->statusValue) {
            'accepted' => "Good news! Your application for {$this->jobTitle} at {$this->company} has been accepted. The employer will review it next.",
            'hired' => "Congratulations! {$this->company} has accepted you for the {$this->jobTitle} role. They will contact you with the next steps.",
            default => "{$this->company} moved your application for {$this->jobTitle} to {$this->statusLabel}.",
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->headline().': '.$this->jobTitle)
            ->greeting("Hello {$notifiable->name},")
            ->line($this->message());

        if ($this->note) {
            $mail->line("Message: {$this->note}");
        }

        return $mail->action('Track your application', route('candidate.applications.show', $this->applicationId));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->headline(),
            'body' => $this->message(),
            'url' => route('candidate.applications.show', $this->applicationId, false),
        ];
    }
}