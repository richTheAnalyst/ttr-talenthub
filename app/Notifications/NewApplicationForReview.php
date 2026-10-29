<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Notifications\Notification;

/** In-app (and push) alert for admins: a candidate applied and needs reviewing. */
class NewApplicationForReview extends Notification
{
    public string $candidate;
    public string $jobTitle;
    public string $company;

    public function __construct(Application $application)
    {
        $application->loadMissing('candidate', 'job.employer');

        $this->candidate = $application->candidate->name;
        $this->jobTitle = $application->job->title;
        $this->company = $application->job->employer->company_name;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New application to review',
            'body' => "{$this->candidate} applied for {$this->jobTitle} at {$this->company}.",
            'url' => route('admin.applications.index', ['status' => 'submitted'], false),
        ];
    }
}