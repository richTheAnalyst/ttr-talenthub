<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Notifications\InterviewNotice;
use App\Services\AdminAlerts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InterviewController extends Controller
{
    /** The candidate confirms or declines the time that was sent to them. */
    public function respond(Request $request, Interview $interview): RedirectResponse
    {
        $interview->loadMissing('application.job.employer.user', 'application.candidate');
        abort_unless($interview->application->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'response' => ['required', Rule::in(['confirm', 'decline'])],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($interview->status !== Interview::PROPOSED || $interview->scheduled_at->isPast()) {
            return back()->with('status', 'This interview time can no longer be changed.');
        }

        $confirmed = $data['response'] === 'confirm';

        $interview->update([
            'status' => $confirmed ? Interview::CONFIRMED : Interview::DECLINED,
            'decline_reason' => $confirmed ? null : ($data['reason'] ?? null),
            'responded_at' => now(),
        ]);

        $application = $interview->application;
        $event = $confirmed ? 'confirmed' : 'declined';

        $application->job->employer->user?->notify(new InterviewNotice($interview, $event, 'employer'));

        AdminAlerts::notify(
            $confirmed ? 'Interview confirmed' : 'Interview declined',
            "{$application->candidate->name} {$event} the interview for {$application->job->title} at {$application->job->employer->company_name} ({$interview->whenText()}).",
            route('admin.applications.index', [], false)
        );

        return back()->with('status', $confirmed
            ? 'Interview confirmed. We will remind you the day before.'
            : 'Interview declined. The employer has been told.');
    }
}