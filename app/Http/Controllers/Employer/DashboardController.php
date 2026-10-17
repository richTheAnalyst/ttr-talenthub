<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $employer = $request->user()->employer;
        $mine = JobPosting::where('employer_id', $employer->id);

        // One aggregate query for the three application counters instead of three round trips.
        $counts = Application::query()
            ->whereIn('job_posting_id', (clone $mine)->select('id'))
            ->selectRaw('count(*) as total, sum(case when status = ? then 1 else 0 end) as to_review, sum(case when status = ? then 1 else 0 end) as interviews', [
                ApplicationStatus::Submitted->value,
                ApplicationStatus::Interview->value,
            ])
            ->first();

        return view('employer.dashboard', [
            'employer' => $employer,
            'stats' => [
                'open_jobs' => (clone $mine)->open()->count(),
                'applicants' => (int) $counts->total,
                'to_review' => (int) $counts->to_review,
                'interviews' => (int) $counts->interviews,
            ],
            'recent' => Application::query()
                ->whereIn('job_posting_id', (clone $mine)->select('id'))
                ->with(['candidate.candidateProfile', 'job'])
                ->latest()
                ->limit(6)
                ->get(),
            'jobs' => (clone $mine)->withCount('applications')->latest()->limit(5)->get(),
        ]);
    }
}