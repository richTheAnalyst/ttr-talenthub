<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Interview;
use App\Notifications\InterviewNotice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Admins can schedule and cancel interviews for any application, with no approval step. */
class InterviewController extends Controller
{
    public function store(Request $request, Application $application): RedirectResponse
    {
        if ($application->status->isFinal()) {
            return back()->withErrors(['scheduled_at' => 'This application is closed, so no interview can be scheduled.']);
        }

        $data = $request->validate(Interview::rules());

        if ($application->interviews()->active()->where('scheduled_at', '>', now())->exists()) {
            return back()->withInput()->withErrors(['scheduled_at' => 'Cancel the current interview before proposing a new time.']);
        }

        $interview = $application->interviews()->create($data + ['status' => Interview::PROPOSED]);

        $application->loadMissing('candidate', 'job.employer.user');
        $application->candidate->notify(new InterviewNotice($interview, 'proposed', 'candidate'));
        $application->job->employer->user?->notify(new InterviewNotice($interview, 'proposed', 'employer'));

        return back()->with('status', 'Interview scheduled. The candidate and the employer have been notified.');
    }

    public function cancel(Interview $interview): RedirectResponse
    {
        if ($interview->isActive()) {
            $visible = $interview->status !== Interview::PENDING_APPROVAL;

            $interview->update(['status' => Interview::CANCELLED]);

            $application = $interview->application->loadMissing('candidate', 'job.employer.user');

            if ($visible) {
                $application->candidate->notify(new InterviewNotice($interview, 'cancelled', 'candidate'));
            }
            $application->job->employer->user?->notify(new InterviewNotice($interview, 'cancelled', 'employer'));
        }

        return back()->with('status', 'Interview cancelled. Everyone involved has been notified.');
    }
}