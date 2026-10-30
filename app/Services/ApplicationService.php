<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Cv;
use App\Models\JobPosting;
use App\Models\Placement;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\ApplicationStatusChanged;
use App\Notifications\CandidateHired;
use App\Notifications\NewApplicationForReview;
use App\Notifications\NewApplicationReceived;
use Illuminate\Support\Facades\Notification;
use App\Enums\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationService
{
    public function __construct(private MatchingService $matching)
    {
    }

    /**
     * Submit an application, score it, record the first history entry and tell the employer.
     *
     * @throws ValidationException when the job is closed or the candidate already applied
     */
    public function apply(User $user, JobPosting $job, ?Cv $cv = null, ?string $note = null): Application
    {
        if (! $job->isOpen()) {
            throw ValidationException::withMessages(['job' => 'This job is no longer accepting applications.']);
        }

        if (Application::where('job_posting_id', $job->id)->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages(['job' => 'You have already applied for this job.']);
        }

        if ($cv && $cv->user_id !== $user->id) {
            throw ValidationException::withMessages(['cv_id' => 'Choose one of your own CVs.']);
        }

        $cv ??= $user->cvs()->where('is_primary', true)->first() ?? $user->cvs()->latest()->first();

        if (! $cv) {
            throw ValidationException::withMessages(['cv_id' => 'Upload a CV before you apply.']);
        }

        $profile = CandidateProfile::with('skills')->firstOrCreate(['user_id' => $user->id]);
        $job->loadMissing('skills', 'employer.user');
        $score = $this->matching->scoreFor($profile, $job)['score'];

        $application = DB::transaction(function () use ($user, $job, $cv, $note, $score) {
            $application = Application::create([
                'job_posting_id' => $job->id,
                'user_id' => $user->id,
                'cv_id' => $cv->id,
                'cover_note' => $note,
                'status' => ApplicationStatus::Submitted,
                'match_score' => $score,
                'status_changed_at' => now(),
            ]);

            $application->histories()->create([
                'from_status' => null,
                'to_status' => ApplicationStatus::Submitted,
                'changed_by' => $user->id,
            ]);

            return $application;
        });

        $job->employer->user->notify(new NewApplicationReceived($application));

        // Admins review every application, so they get an alert too.
        Notification::send(User::where('role', Role::Admin->value)->where('is_active', true)->get(), new NewApplicationForReview($application));

        return $application;
    }

    /** Move an application to a new status, log it and tell the candidate. */
    public function changeStatus(Application $application, ApplicationStatus $to, ?string $note, ?User $actor): Application
    {
        $from = $application->status;

        if ($from === $to) {
            return $application;
        }

        if ($from === ApplicationStatus::Withdrawn) {
            throw ValidationException::withMessages(['status' => 'The candidate withdrew this application.']);
        }

        DB::transaction(function () use ($application, $from, $to, $note, $actor) {
            $application->update(['status' => $to, 'status_changed_at' => now()]);

            $application->histories()->create([
                'from_status' => $from,
                'to_status' => $to,
                'note' => $note,
                'changed_by' => $actor?->id,
            ]);
        });

        $application->loadMissing('job.employer', 'candidate');

        if ($to !== ApplicationStatus::Withdrawn) {
            $application->candidate->notify(new ApplicationStatusChanged($application, $note));
        }

        // Admins act as the middleman, so they hear about what employers and candidates do.
        if ($actor && ! $actor->isAdmin()) {
            AdminAlerts::notify(
                'Application update',
                "{$actor->name} set {$application->candidate->name}'s application for {$application->job->title} ({$application->job->employer->company_name}) to {$to->label()}.",
                route('admin.applications.index', [], false)
            );
        }

        // Hired: give the employer everything they need to contact the new employee.
        if ($to === ApplicationStatus::Hired) {
            $application->loadMissing('job.employer.user');
            $application->job->employer->user->notify(new CandidateHired($application));
            $this->recordPlacement($application);
        }

        return $application->refresh();
    }

    public function withdraw(Application $application, User $actor): Application
    {
        if ($application->status->isFinal()) {
            throw ValidationException::withMessages(['status' => 'This application is already closed.']);
        }

        return $this->changeStatus($application, ApplicationStatus::Withdrawn, 'Withdrawn by the candidate.', $actor);
    }

    /** Record the hire so the monthly commission can be tracked. Admins can correct the salary and rate. */
    private function recordPlacement(Application $application): void
    {
        $job = $application->job;

        $salary = match (true) {
            $job->salary_min && $job->salary_max => (int) round(($job->salary_min + $job->salary_max) / 2),
            (bool) $job->salary_min => (int) $job->salary_min,
            (bool) $job->salary_max => (int) $job->salary_max,
            default => null,
        };

        $outsourcing = $job->engagement === 'outsourcing';

        Placement::firstOrCreate(
            ['application_id' => $application->id],
            [
                'employer_id' => $job->employer_id,
                'user_id' => $application->user_id,
                'engagement' => $job->engagement ?: 'recruitment',
                'monthly_salary' => $salary,
                'client_monthly' => $outsourcing ? $job->client_budget : null,
                'one_time_fee' => $outsourcing ? null : $job->placement_fee,
                'currency' => $job->currency ?: 'GHS',
                'commission_rate' => (float) ($job->fee_rate ?? Setting::read('commission_rate', '15')),
                'starts_on' => now()->toDateString(),
                'status' => 'active',
            ]
        );

        AdminAlerts::notify(
            'New placement recorded',
            "{$application->candidate->name} was hired for {$job->title} at {$job->employer->company_name}. Check the money details on the Placements page.",
            route('admin.placements.index', [], false),
            force: true
        );
    }
}