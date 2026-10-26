<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedJobController extends Controller
{
    public function index(Request $request): View
    {
        return view('candidate.saved', [
            'jobs' => $request->user()->savedJobs()->with('employer')->latest('saved_jobs.created_at')->get(),
        ]);
    }

    public function toggle(Request $request, JobPosting $job): RedirectResponse
    {
        $changes = $request->user()->savedJobs()->toggle($job->id);
        $saved = ! empty($changes['attached']);

        return back()->with('status', $saved ? 'Job saved.' : 'Removed from saved jobs.');
    }
}