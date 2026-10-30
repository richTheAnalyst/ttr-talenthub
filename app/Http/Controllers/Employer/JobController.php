<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use App\Services\JobPostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class JobController extends Controller
{
    public function index(Request $request): View
    {
        $jobs = JobPosting::where('employer_id', $request->user()->employer->id)
            ->withCount('applications')
            ->latest()
            ->simplePaginate(15);

        return view('employer.jobs.index', compact('jobs'));
    }

    public function create(): View
    {
        return view('employer.jobs.form', ['job' => null]);
    }

    public function store(Request $request, JobPostingService $service): RedirectResponse
    {
        $data = $request->validate(JobPostingService::rules());

        $job = $service->save($request->user()->employer, null, $data);

        return redirect()->route('employer.jobs.index')->with('status', $this->savedMessage($job));
    }

    public function edit(JobPosting $job): View
    {
        Gate::authorize('manage', $job);

        $job->load('skills');

        return view('employer.jobs.form', [
            'job' => $job,
            'requiredSkills' => $job->skills->filter(fn ($s) => $s->pivot->is_required)->pluck('name')->implode(', '),
            'optionalSkills' => $job->skills->reject(fn ($s) => $s->pivot->is_required)->pluck('name')->implode(', '),
        ]);
    }

    public function update(Request $request, JobPosting $job, JobPostingService $service): RedirectResponse
    {
        Gate::authorize('manage', $job);

        $data = $request->validate(JobPostingService::rules());

        $job = $service->save($job->employer, $job, $data);

        return redirect()->route('employer.jobs.index')->with('status', $this->savedMessage($job));
    }

    public function close(JobPosting $job): RedirectResponse
    {
        Gate::authorize('manage', $job);

        $job->update(['status' => JobPosting::STATUS_CLOSED]);

        return back()->with('status', 'Job closed. It no longer appears in search.');
    }

    public function destroy(JobPosting $job): RedirectResponse
    {
        Gate::authorize('manage', $job);

        if ($job->applications()->exists()) {
            return back()->withErrors(['job' => 'This job has applicants, so it cannot be deleted. Close it instead.']);
        }

        $job->delete();

        return redirect()->route('employer.jobs.index')->with('status', 'Job deleted.');
    }

    private function savedMessage(JobPosting $job): string
    {
        if ($job->status !== JobPosting::STATUS_OPEN) {
            return 'Draft saved. Publish it when you are ready.';
        }

        return $job->isAwaitingApproval()
            ? 'Job submitted. It goes live as soon as TTR approves it, and you will be notified.'
            : 'Job saved.';
    }
}