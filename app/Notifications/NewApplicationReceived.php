<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewApplicationReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public int $jobId;
    public string $jobTitle;
    public string $applicant;
    public int $score;

    public function __construct(Application $application)
    {
        $application->loadMissing('job', 'candidate');

        $this->jobId = $application->job_posting_id;
        $this->jobTitle = $application->job->title;
        $this->applicant = $application->candidate->name;
        $this->score = $application->match_score;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New applicant for {$this->jobTitle}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->applicant} applied for {$this->jobTitle}. Their profile is a {$this->score}% match.")
            ->action('Review applicants', route('employer.jobs.applicants', $this->jobId));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New applicant',
            'body' => "{$this->applicant} applied for {$this->jobTitle} ({$this->score}% match).",
            'url' => route('employer.jobs.applicants', $this->jobId, false),
        ];
    }
}
