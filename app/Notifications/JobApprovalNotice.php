<?php

namespace App\Notifications;

use App\Models\JobPosting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** $event: submitted (to admins) | approved | rejected (to the employer). */
class JobApprovalNotice extends Notification implements ShouldQueue
{
    use Queueable;

    public string $jobTitle;
    public string $company;
    public ?string $reason;

    public function __construct(JobPosting $job, public string $event)
    {
        $job->loadMissing('employer');

        $this->jobTitle = $job->title;
        $this->company = $job->employer->company_name;
        $this->reason = $job->approval_note;
    }

    public function via(object $notifiable): array
    {
        return $this->event === 'submitted' ? ['database'] : ['database', 'mail'];
    }

    private function headline(): string
    {
        return match ($this->event) {
            'submitted' => 'Job waiting for approval',
            'approved' => 'Your job is live',
            default => 'Your job was not approved',
        };
    }

    private function message(): string
    {
        return match ($this->event) {
            'submitted' => "{$this->company} submitted \"{$this->jobTitle}\" for approval.",
            'approved' => "\"{$this->jobTitle}\" has been approved and is now visible to candidates.",
            default => "\"{$this->jobTitle}\" was not approved.".($this->reason ? " Reason: {$this->reason}" : '').' You can edit it and submit it again.',
        };
    }

    private function url(bool $absolute = true): string
    {
        return $this->event === 'submitted'
            ? route('admin.approvals.index', [], $absolute)
            : route('employer.jobs.index', [], $absolute);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->headline().': '.$this->jobTitle)
            ->greeting("Hello {$notifiable->name},")
            ->line($this->message())
            ->action('Open my jobs', $this->url());
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => $this->headline(), 'body' => $this->message(), 'url' => $this->url(false)];
    }
}