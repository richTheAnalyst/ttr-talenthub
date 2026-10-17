<?php

namespace App\Notifications;

use App\Models\JobPosting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "A job that fits you was just posted" (or, with $invited, "an employer invited you to apply").
 */
class JobAlertMatch extends Notification implements ShouldQueue
{
    use Queueable;

    public int $jobId;
    public string $jobTitle;
    public string $company;

    public function __construct(
        JobPosting $job,
        public int $score,
        public bool $viaMail = true,
        public bool $invited = false,
    ) {
        $job->loadMissing('employer');

        $this->jobId = $job->id;
        $this->jobTitle = $job->title;
        $this->company = $job->employer->company_name;
    }

    public function via(object $notifiable): array
    {
        return $this->viaMail ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lead = $this->invited
            ? "{$this->company} invited you to apply for {$this->jobTitle}."
            : "{$this->company} just posted {$this->jobTitle}.";

        return (new MailMessage)
            ->subject($this->invited ? "You're invited: {$this->jobTitle}" : "New job for you: {$this->jobTitle}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$lead} It is a {$this->score}% match for your profile.")
            ->action('See the job', route('jobs.show', $this->jobId));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->invited ? 'You were invited to apply' : 'A job fits your profile',
            'body' => "{$this->jobTitle} at {$this->company} ({$this->score}% match).",
            'url' => route('jobs.show', $this->jobId, false),
        ];
    }
}
