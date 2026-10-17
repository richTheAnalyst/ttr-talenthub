<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\JobPosting;
use App\Services\MatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PublicJobController extends Controller
{
    public function index(Request $request, MatchingService $matching): View
    {
        $filters = $request->only('q', 'type', 'location');

        $jobs = JobPosting::open()
            ->with(['employer', 'skills'])
            ->filter($filters)
            ->latest('published_at')
            ->simplePaginate(12)
            ->withQueryString();

        // Signed-in candidates see how well each job fits them.
        $scores = [];
        $user = $request->user();
        if ($user?->isCandidate()) {
            $profile = CandidateProfile::with('skills')->firstOrCreate(['user_id' => $user->id]);
            foreach ($jobs as $job) {
                $scores[$job->id] = $matching->scoreFor($profile, $job)['score'];
            }
        }

        return view('jobs.index', compact('jobs', 'filters', 'scores'));
    }

    public function show(Request $request, MatchingService $matching, JobPosting $job): View
    {
        $user = $request->user();

        abort_unless($job->isOpen() || ($user && Gate::allows('manage', $job)), 404);

        $job->load(['employer', 'skills']);

        $match = null;
        $application = null;
        $cvs = collect();

        if ($user?->isCandidate()) {
            $profile = CandidateProfile::with('skills')->firstOrCreate(['user_id' => $user->id]);
            $match = $matching->scoreFor($profile, $job);
            $application = Application::where('job_posting_id', $job->id)->where('user_id', $user->id)->first();
            $cvs = $user->cvs()->latest()->get();
        }

        return view('jobs.show', compact('job', 'match', 'application', 'cvs'));
    }
}
