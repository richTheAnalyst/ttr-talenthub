<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\HiringRequest;
use App\Models\JobPosting;
use App\Models\Setting;
use App\Services\JobPostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admins post jobs for an employer. These go live at once, with the same 15% / fixed-fee rules. */
class JobController extends Controller
{
    public function create(Request $request): View
    {
        $prefill = null;
        if ($request->filled('request')) {
            $prefill = HiringRequest::find($request->query('request'));
        }

        return view('admin.jobs.form', [
            'employers' => Employer::orderBy('company_name')->get(['id', 'company_name']),
            'hr' => $prefill,
            'rate' => (float) Setting::read('commission_rate', '15'),
        ]);
    }

    public function store(Request $request, JobPostingService $service): RedirectResponse
    {
        $data = $request->validate(JobPostingService::rules() + [
            'employer_id' => ['required', 'exists:employers,id'],
            'placement_fee' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'hiring_request_id' => ['nullable', 'exists:hiring_requests,id'],
        ]);

        if (($data['engagement'] ?? 'recruitment') === 'recruitment' && ! isset($data['placement_fee'])) {
            return back()->withInput()->withErrors(['placement_fee' => 'Set the one-time fee for this role.']);
        }

        $data['action'] = 'publish';
        $employer = Employer::with('user')->findOrFail($data['employer_id']);

        $job = $service->save($employer, null, $data, autoApprove: true);

        if ($job->isOutsourcing()) {
            $rate = (float) Setting::read('commission_rate', '15');
            $pay = $job->workerPay($rate);
            $job->update(['salary_min' => $pay, 'salary_max' => $pay, 'fee_rate' => $rate]);
        } else {
            $job->update(['placement_fee' => $data['placement_fee']]);
        }

        if (! empty($data['hiring_request_id'])) {
            HiringRequest::whereKey($data['hiring_request_id'])->where('status', 'new')->update(['status' => 'in_progress']);
        }

        return redirect()->route('admin.approvals.index')->with('status', 'Job posted and live for '.$employer->company_name.'.');
    }
}