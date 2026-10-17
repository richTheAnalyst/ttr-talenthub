<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Cv;
use App\Models\JobPosting;
use App\Services\ApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $applications = $request->user()->applications()
            ->with('job.employer')
            ->when(
                $status && ApplicationStatus::tryFrom($status),
                fn ($q) => $q->where('status', $status)
            )
            ->latest('status_changed_at')
            ->simplePaginate(15)
            ->withQueryString();

        return view('candidate.applications.index', compact('applications', 'status'));
    }

    public function show(Application $application): View
    {
        Gate::authorize('view', $application);

        $application->load(['job.employer', 'cv', 'histories.author']);

        return view('candidate.applications.show', compact('application'));
    }

    public function store(Request $request, JobPosting $job, ApplicationService $applications): RedirectResponse
    {
        $data = $request->validate([
            'cv_id' => ['nullable', 'integer', Rule::exists('cvs', 'id')->where('user_id', $request->user()->id)],
            'cover_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $cv = ! empty($data['cv_id']) ? Cv::find($data['cv_id']) : null;

        $application = $applications->apply($request->user(), $job, $cv, $data['cover_note'] ?? null);

        return redirect()->route('candidate.applications.show', $application)
            ->with('status', 'Application sent. You will be notified when the employer responds.');
    }

    public function withdraw(Request $request, Application $application, ApplicationService $applications): RedirectResponse
    {
        Gate::authorize('withdraw', $application);

        $applications->withdraw($application, $request->user());

        return back()->with('status', 'Application withdrawn.');
    }
}
