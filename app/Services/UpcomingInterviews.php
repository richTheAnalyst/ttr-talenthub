<?php

namespace App\Services;

use App\Models\Interview;
use App\Models\User;
use Illuminate\Support\Collection;

/** The next few interviews for a dashboard, shaped as plain rows the shared partial can print. */
class UpcomingInterviews
{
    public function forCandidate(User $user, int $limit = 4): Collection
    {
        return $this->query()
            ->whereIn('status', [Interview::PROPOSED, Interview::CONFIRMED])
            ->whereHas('application', fn ($q) => $q->where('user_id', $user->id))
            ->limit($limit)->get()
            ->map(fn (Interview $i) => $this->row(
                $i,
                $i->application->job->title,
                $i->application->job->employer->company_name,
                route('candidate.applications.show', $i->application_id)
            ));
    }

    public function forEmployer(int $employerId, int $limit = 4): Collection
    {
        return $this->query()
            ->whereHas('application.job', fn ($q) => $q->where('employer_id', $employerId))
            ->limit($limit)->get()
            ->map(fn (Interview $i) => $this->row(
                $i,
                $i->application->candidate->name,
                $i->application->job->title,
                route('employer.jobs.applicants', $i->application->job_posting_id)
            ));
    }

    public function forAdmin(int $limit = 6): Collection
    {
        return $this->query()->limit($limit)->get()
            ->map(fn (Interview $i) => $this->row(
                $i,
                $i->application->candidate->name.' · '.$i->application->job->title,
                $i->application->job->employer->company_name,
                route('admin.applications.index')
            ));
    }

    private function query()
    {
        return Interview::active()
            ->where('scheduled_at', '>=', now())
            ->with(['application.candidate:id,name', 'application.job.employer:id,company_name'])
            ->orderBy('scheduled_at');
    }

    private function row(Interview $i, string $title, string $subtitle, string $url): array
    {
        return [
            'when' => $i->whenText(),
            'soon' => $i->scheduled_at->lt(now()->addDay()),
            'title' => $title,
            'subtitle' => $subtitle,
            'mode' => $i->modeLabel(),
            'status' => $i->status,
            'url' => $url,
        ];
    }
}