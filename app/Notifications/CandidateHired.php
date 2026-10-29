<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the employer when a candidate is accepted for their job, with everything they need to follow up. */
class CandidateHired extends Notification implements ShouldQueue
{
    use Queueable;

    public int $jobId;
    public string $jobTitle;
    public string $name;
    public ?string $email;
    public ?string $phone;
    public ?string $headline;
    public ?string $location;
    public int $years;
    public ?string $education;
    public ?string $availability;
    public ?int $expectedSalary;
    public array $skills;
    public ?int $cvId;
    public ?string $coverNote;

    public function __construct(Application $application)
    {
        $application->loadMissing('job', 'candidate.candidateProfile.skills', 'cv');

        $user = $application->candidate;
        $profile = $user->candidateProfile;

        $this->jobId = $application->job->id;
        $this->jobTitle = $application->job->title;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->headline = $profile?->headline ?: $profile?->desired_title;
        $this->location = $profile?->location;
        $this->years = (int) ($profile?->years_experience ?? 0);
        $this->education = $profile?->education_level ? (config('talenthub.education_levels')[$profile->education_level] ?? $profile->education_level) : null;
        $this->availability = $profile?->availability ? (config('talenthub.availabilities')[$profile->availability] ?? $profile->availability) : null;
        $this->expectedSalary = $profile?->expected_salary;
        $this->skills = $profile ? $profile->skills->pluck('name')->all() : [];
        $this->cvId = $application->cv?->id;
        $this->coverNote = $application->cover_note;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /** @return list<string> */
    private function details(): array
    {
        return array_values(array_filter([
            "Name: {$this->name}",
            $this->email ? "Email: {$this->email}" : null,
            $this->phone ? "Phone: {$this->phone}" : null,
            $this->headline ? "Profile: {$this->headline}" : null,
            $this->location ? "Location: {$this->location}" : null,
            "Experience: {$this->years} year".($this->years === 1 ? '' : 's'),
            $this->education ? "Education: {$this->education}" : null,
            $this->availability ? "Available: {$this->availability}" : null,
            $this->expectedSalary ? 'Expected salary: GHS '.number_format($this->expectedSalary) : null,
            $this->skills ? 'Skills: '.implode(', ', $this->skills) : null,
        ]));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("{$this->name} has been accepted for {$this->jobTitle}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->name} has been accepted for your {$this->jobTitle} role. Here is everything you need to get in touch:");

        foreach ($this->details() as $line) {
            $mail->line($line);
        }

        if ($this->coverNote) {
            $mail->line('Cover note: '.$this->coverNote);
        }

        if ($this->cvId) {
            $mail->action('Download their CV', route('cv.download', $this->cvId));
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => "{$this->name} accepted for {$this->jobTitle}",
            'body' => implode("\n", $this->details()),
            'url' => route('employer.jobs.applicants', $this->jobId, false),
        ];
    }
}