<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessNewJobPosting;
use App\Models\Interview;
use App\Models\JobPosting;
use App\Models\Setting;
use App\Notifications\InterviewNotice;
use App\Notifications\JobApprovalNotice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admins approve new or edited jobs and employer-proposed interviews before they go any further. */
class ApprovalController extends Controller
{
    public function index(): View
    {
        return view('admin.approvals', [
            'rate' => (float) Setting::read('commission_rate', '15'),
            'jobs' => JobPosting::where('status', JobPosting::STATUS_OPEN)
                ->where('approval_status', JobPosting::APPROVAL_PENDING)
                ->with(['employer', 'skills'])
                ->oldest('updated_at')
                ->get(),
            'interviews' => Interview::where('status', Interview::PENDING_APPROVAL)
                ->with(['application.candidate', 'application.job.employer'])
                ->oldest()
                ->get(),
        ]);
    }

    public function approveJob(Request $request, JobPosting $job): RedirectResponse
    {
        $first = $job->approved_at === null;

        $changes = [
            'approval_status' => JobPosting::APPROVAL_APPROVED,
            'approval_note' => null,
            'approved_at' => $job->approved_at ?? now(),
        ];

        if ($job->isOutsourcing()) {
            // Take TTR's percentage off the client's budget; the worker sees what is left.
            if (! $job->client_budget) {
                return back()->withErrors(['job' => 'This outsourcing job has no monthly budget.']);
            }

            $rate = (float) Setting::read('commission_rate', '15');
            $pay = $job->workerPay($rate);
            $changes += ['salary_min' => $pay, 'salary_max' => $pay, 'fee_rate' => $rate];
        } else {
            $data = $request->validate(['placement_fee' => ['required', 'integer', 'min:0', 'max:100000000']]);
            $changes['placement_fee'] = $data['placement_fee'];
        }

        $job->update($changes);

        $job->loadMissing('employer.user');
        $job->employer->user?->notify(new JobApprovalNotice($job, 'approved'));

        // Candidates are matched and alerted the first time a job goes live.
        if ($first) {
            ProcessNewJobPosting::dispatch($job->id);
        }

        return back()->with('status', 'Job approved. It is now live.');
    }

    public function rejectJob(Request $request, JobPosting $job): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $job->update(['approval_status' => JobPosting::APPROVAL_REJECTED, 'approval_note' => $data['reason']]);

        $job->loadMissing('employer.user');
        $job->employer->user?->notify(new JobApprovalNotice($job, 'rejected'));

        return back()->with('status', 'Job not approved. The employer has been told why.');
    }

    public function approveInterview(Interview $interview): RedirectResponse
    {
        if ($interview->status === Interview::PENDING_APPROVAL) {
            $interview->update(['status' => Interview::PROPOSED]);

            $application = $interview->application->loadMissing('candidate', 'job.employer.user');
            $application->candidate->notify(new InterviewNotice($interview, 'proposed', 'candidate'));
            $application->job->employer->user?->notify(new InterviewNotice($interview, 'approved', 'employer'));
        }

        return back()->with('status', 'Interview approved and sent to the candidate.');
    }

    public function rejectInterview(Request $request, Interview $interview): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        if ($interview->status === Interview::PENDING_APPROVAL) {
            $interview->update(['status' => Interview::REJECTED, 'decline_reason' => $data['reason']]);

            $application = $interview->application->loadMissing('job.employer.user');
            $application->job->employer->user?->notify(new InterviewNotice($interview, 'rejected', 'employer'));
        }

        return back()->with('status', 'Interview not approved. The employer has been told why.');
    }
}