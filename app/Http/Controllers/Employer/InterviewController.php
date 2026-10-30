<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Interview;
use App\Models\Setting;
use App\Notifications\InterviewNotice;
use App\Services\AdminAlerts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InterviewController extends Controller
{
    /** Propose an interview time. With approval switched on, an admin must approve it before the candidate sees it. */
    public function store(Request $request, Application $application): RedirectResponse
    {
        Gate::authorize('updateStatus', $application);

        if ($application->status->isFinal()) {
            return back()->withErrors(['scheduled_at' => 'This application is closed, so no interview can be scheduled.']);
        }

        $data = $request->validate(Interview::rules());

        if ($application->interviews()->active()->where('scheduled_at', '>', now())->exists()) {
            return back()->withInput()->withErrors(['scheduled_at' => 'Cancel the current interview before proposing a new time.']);
        }

        $needsApproval = Setting::read('interview_approval', '1') === '1';

        $interview = $application->interviews()->create($data + [
            'status' => $needsApproval ? Interview::PENDING_APPROVAL : Interview::PROPOSED,
        ]);

        $application->loadMissing('candidate', 'job.employer');
        $summary = "{$application->job->employer->company_name} and {$application->candidate->name} for {$application->job->title} on {$interview->whenText()}";

        if ($needsApproval) {
            AdminAlerts::notify('Interview waiting for approval', "Interview proposed: {$summary}.", route('admin.approvals.index', [], false), force: true);

            return back()->with('status', 'Interview sent to TTR for approval. The candidate is told as soon as it is approved.');
        }

        $application->candidate->notify(new InterviewNotice($interview, 'proposed', 'candidate'));
        AdminAlerts::notify('Interview proposed', "Interview proposed: {$summary}.", route('admin.applications.index', [], false));

        return back()->with('status', 'Interview time sent. The candidate has been notified.');
    }

    public function cancel(Interview $interview): RedirectResponse
    {
        Gate::authorize('updateStatus', $interview->application);

        if ($interview->isActive()) {
            $visible = $interview->status !== Interview::PENDING_APPROVAL;

            $interview->update(['status' => Interview::CANCELLED]);

            if ($visible) {
                $interview->application->candidate->notify(new InterviewNotice($interview, 'cancelled', 'candidate'));
            }

            $application = $interview->application->loadMissing('candidate', 'job.employer');
            AdminAlerts::notify('Interview cancelled', "{$application->job->employer->company_name} cancelled the interview with {$application->candidate->name} for {$application->job->title} ({$interview->whenText()}).");
        }

        return back()->with('status', 'Interview cancelled.');
    }
}