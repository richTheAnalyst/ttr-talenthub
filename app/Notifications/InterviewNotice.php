<?php

namespace App\Notifications;

use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One notification for the whole interview flow.
 * $event: proposed | approved | rejected | confirmed | declined | cancelled | reminder
 * $audience: candidate | employer (decides the wording and the link)
 */
class InterviewNotice extends Notification implements ShouldQueue
{
    use Queueable;

    public int $applicationId;
    public int $jobId;
    public string $jobTitle;
    public string $company;
    public string $candidateName;
    public string $when;
    public string $mode;
    public ?string $details;
    public ?string $reason;

    public function __construct(Interview $interview, public string $event, public string $audience)
    {
        $interview->loadMissing('application.candidate', 'application.job.employer');

        $application = $interview->application;

        $this->applicationId = $application->id;
        $this->jobId = $application->job->id;
        $this->jobTitle = $application->job->title;
        $this->company = $application->job->employer->company_name;
        $this->candidateName = $application->candidate->name;
        $this->when = $interview->whenText();
        $this->mode = $interview->modeLabel();
        $this->details = $interview->details;
        $this->reason = $interview->decline_reason;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    private function headline(): string
    {
        return match ($this->event) {
            'proposed' => $this->audience === 'candidate' ? 'Interview invitation' : 'Interview scheduled',
            'approved' => 'Interview approved',
            'rejected' => 'Interview not approved',
            'confirmed' => 'Interview confirmed',
            'declined' => 'Interview declined',
            'cancelled' => 'Interview cancelled',
            default => 'Interview reminder',
        };
    }

    private function message(): string
    {
        $candidate = $this->audience === 'candidate';

        return match ($this->event) {
            'proposed' => $candidate
                ? "{$this->company} would like to interview you for {$this->jobTitle} on {$this->when} ({$this->mode}). Please confirm or decline."
                : "An interview with {$this->candidateName} for {$this->jobTitle} was scheduled for {$this->when} ({$this->mode}) and sent to the candidate to confirm.",
            'approved' => "TTR approved your interview with {$this->candidateName} for {$this->jobTitle} on {$this->when}. It has been sent to the candidate.",
            'rejected' => "TTR did not approve your interview with {$this->candidateName} for {$this->jobTitle} on {$this->when}.".($this->reason ? " Reason: {$this->reason}" : ''),
            'confirmed' => "{$this->candidateName} confirmed the interview for {$this->jobTitle} on {$this->when}.",
            'declined' => "{$this->candidateName} cannot make the interview for {$this->jobTitle} on {$this->when}.".($this->reason ? " Reason: {$this->reason}" : ''),
            'cancelled' => "The interview for {$this->jobTitle} that was planned for {$this->when} was cancelled.",
            default => $candidate
                ? "Reminder: your interview with {$this->company} for {$this->jobTitle} is on {$this->when} ({$this->mode})."
                : "Reminder: you are interviewing {$this->candidateName} for {$this->jobTitle} on {$this->when} ({$this->mode}).",
        };
    }

    private function url(bool $absolute = true): string
    {
        return $this->audience === 'candidate'
            ? route('candidate.applications.show', $this->applicationId, $absolute)
            : route('employer.jobs.applicants', $this->jobId, $absolute).'#application-'.$this->applicationId;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->headline().': '.$this->jobTitle)
            ->greeting("Hello {$notifiable->name},")
            ->line($this->message());

        if ($this->details && in_array($this->event, ['proposed', 'approved', 'confirmed', 'reminder'], true)) {
            $mail->line("Where: {$this->details}");
        }

        return $mail->action($this->audience === 'candidate' ? 'View interview' : 'View applicant', $this->url());
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->headline(),
            'body' => $this->message(),
            'url' => $this->url(false),
        ];
    }
}