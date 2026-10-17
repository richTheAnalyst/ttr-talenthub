<?php

namespace App\Http\Controllers;

use App\Models\HiringRequest;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Public lead capture: study-abroad enquiries and employer hiring requests.
 * Both work without an account, and both land in the CRM through LeadService.
 */
class LeadController extends Controller
{
    public function study(): View
    {
        return view('leads.study-abroad');
    }

    public function storeStudy(Request $request, LeadService $leads): RedirectResponse
    {
        // Honeypot: real visitors never see this field, so bots that fill it get a quiet fake success.
        if ($request->filled('website')) {
            return back()->with('status', 'Thanks. An adviser will contact you within two working days.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['required', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:80'],
            'destination' => ['required', Rule::in(config('talenthub.study_destinations'))],
            'level' => ['required', Rule::in(array_keys(config('talenthub.study_levels')))],
            'intake' => ['nullable', Rule::in(array_keys(config('talenthub.study_intakes')))],
            'field_of_study' => ['nullable', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:1500'],
            'consent' => ['accepted'],
        ]);

        $leads->capture(Lead::TYPE_STUDY_ABROAD, [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'country' => $data['country'] ?? 'Ghana',
            'source' => 'web_form',
            'details' => array_filter([
                'destination' => $data['destination'],
                'level' => $data['level'],
                'intake' => $data['intake'] ?? null,
                'field_of_study' => $data['field_of_study'] ?? null,
                'message' => $data['message'] ?? null,
            ]),
        ], [
            'destination' => $data['destination'],
            'study_level' => $data['level'],
            'consent_marketing' => true,
        ], $request->user());

        return redirect()->route('study.create')
            ->with('status', 'Thanks. An adviser will contact you within two working days.');
    }

    public function hire(Request $request): View
    {
        return view('leads.hire', ['employer' => $request->user()?->employer]);
    }

    public function storeHire(Request $request, LeadService $leads): RedirectResponse
    {
        if ($request->filled('website')) {
            return back()->with('status', 'Request received. We will send you shortlisted candidates soon.');
        }

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:160'],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['required', 'email:rfc', 'max:190'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'role_title' => ['required', 'string', 'max:160'],
            'headcount' => ['required', 'integer', 'min:1', 'max:500'],
            'location' => ['nullable', 'string', 'max:120'],
            'job_type' => ['nullable', Rule::in(array_keys(\App\Enums\JobType::options()))],
            'required_skills' => ['nullable', 'string', 'max:600'],
            'description' => ['nullable', 'string', 'max:3000'],
            'urgency' => ['required', Rule::in(array_keys(config('talenthub.urgencies')))],
            'budget' => ['nullable', 'string', 'max:120'],
        ]);

        $user = $request->user();

        HiringRequest::create($data + [
            'employer_id' => $user?->isEmployer() ? $user->employer?->id : null,
        ]);

        $leads->capture(Lead::TYPE_EMPLOYER, [
            'name' => $data['contact_name'],
            'email' => $data['contact_email'],
            'phone' => $data['contact_phone'] ?? null,
            'country' => 'Ghana',
            'source' => 'web_form',
            'details' => array_filter([
                'company' => $data['company_name'],
                'role' => $data['role_title'],
                'headcount' => $data['headcount'],
                'urgency' => $data['urgency'],
                'location' => $data['location'] ?? null,
            ]),
        ], [
            'urgency' => $data['urgency'],
            'headcount' => $data['headcount'],
        ], $user);

        $message = 'Request received. We will send you shortlisted candidates soon.';

        return $user?->isEmployer()
            ? redirect()->route('employer.hiring.index')->with('status', $message)
            : redirect()->route('hire.create')->with('status', $message);
    }
}
