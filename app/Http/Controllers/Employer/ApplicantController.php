<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\JobPosting;
use App\Notifications\JobAlertMatch;
use App\Services\ApplicationService;
use App\Services\MatchingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ApplicantController extends Controller
{
    public function index(Request $request, MatchingService $matching, JobPosting $job): View
    {
        Gate::authorize('manage', $job);

        $job->load(['skills', 'employer']);
        $status = $request->query('status');

        $applications = $job->applications()
            ->with(['candidate.candidateProfile.skills', 'cv', 'interviews'])
            ->when($status && ApplicationStatus::tryFrom($status), fn ($q) => $q->where('status', $status))
            ->orderByDesc('match_score')
            ->get();

        // Candidates who fit the job but have not applied yet.
        $suggestions = collect();
        if ($job->isOpen()) {
            $appliedUserIds = $job->applications()->pluck('user_id');

            $profiles = CandidateProfile::searchable()
                ->wantsJobs()
                ->whereNotIn('user_id', $appliedUserIds)
                ->whereHas('user', fn ($q) => $q->where('is_active', true))
                ->with(['skills', 'user'])
                ->limit(300)
                ->get();

            $suggestions = $matching->rankCandidates($job, $profiles, 45, 8);
        }

        return view('employer.applicants', [
            'job' => $job,
            'applications' => $applications,
            'suggestions' => $suggestions,
            'status' => $status,
        ]);
    }

    public function updateStatus(Request $request, Application $application, ApplicationService $applications): RedirectResponse
    {
        Gate::authorize('updateStatus', $application);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(ApplicationStatus::employerChoices()))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $applications->changeStatus($application, ApplicationStatus::from($data['status']), $data['note'] ?? null, $request->user());

        return back()->with('status', 'Status updated. The candidate has been notified.');
    }

    /** Invite a matching candidate to apply. One invitation per candidate and job per week. */
    public function invite(Request $request, MatchingService $matching, JobPosting $job, CandidateProfile $profile): RedirectResponse
    {
        Gate::authorize('manage', $job);

        abort_unless($job->isOpen() && $profile->is_searchable, 404);

        if (! Cache::add("invite:{$job->id}:{$profile->user_id}", true, now()->addDays(7))) {
            return back()->with('status', 'This candidate was already invited this week.');
        }

        $job->loadMissing('skills', 'employer');
        $profile->loadMissing('skills', 'user');

        $score = $matching->scoreFor($profile, $job)['score'];
        $profile->user->notify(new JobAlertMatch($job, $score, viaMail: true, invited: true));

        return back()->with('status', 'Invitation sent.');
    }
}