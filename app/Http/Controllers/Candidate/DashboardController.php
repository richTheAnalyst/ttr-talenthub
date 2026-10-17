<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\JobPosting;
use App\Services\MatchingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, MatchingService $matching): View
    {
        $user = $request->user();
        $profile = CandidateProfile::with('skills')->firstOrCreate(['user_id' => $user->id]);

        $matches = $this->matchesFor($user->id, $profile, $matching, 5);

        $applications = $user->applications()->with('job.employer')->latest('status_changed_at')->limit(4)->get();

        $final = array_map(fn (ApplicationStatus $s) => $s->value, array_filter(
            ApplicationStatus::cases(),
            fn (ApplicationStatus $s) => $s->isFinal()
        ));

        $stats = [
            'applications' => $user->applications()->count(),
            'active' => $user->applications()->whereNotIn('status', $final)->count(),
            'alerts' => $user->jobAlerts()->active()->count(),
        ];

        return view('candidate.dashboard', [
            'profile' => $profile,
            'matches' => $matches,
            'applications' => $applications,
            'stats' => $stats,
            'notifications' => $user->unreadNotifications()->limit(3)->get(),
            'hasCv' => $user->cvs()->exists(),
        ]);
    }

    public function matches(Request $request, MatchingService $matching): View
    {
        $user = $request->user();
        $profile = CandidateProfile::with('skills')->firstOrCreate(['user_id' => $user->id]);

        return view('candidate.matches', [
            'profile' => $profile,
            'matches' => $this->matchesFor($user->id, $profile, $matching, 30),
        ]);
    }

    /** Open jobs the candidate has not applied to, best match first. */
    private function matchesFor(int $userId, CandidateProfile $profile, MatchingService $matching, int $limit)
    {
        $applied = Application::where('user_id', $userId)->pluck('job_posting_id')->all();

        $jobs = JobPosting::open()
            ->whereNotIn('id', $applied)
            ->with(['skills', 'employer'])
            ->latest('published_at')
            ->limit(200)
            ->get();

        return $matching->rankJobs(
            $profile,
            $jobs,
            (int) config('talenthub.matching.recommend_min_score', 30),
            $limit
        );
    }
}
