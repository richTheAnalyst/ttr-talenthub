<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\JobType;
use App\Http\Controllers\Controller;
use App\Models\JobAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JobAlertController extends Controller
{
    private const MAX_ALERTS = 10;

    public function index(Request $request): View
    {
        return view('candidate.alerts', [
            'alerts' => $request->user()->jobAlerts()->latest()->get(),
            'max' => self::MAX_ALERTS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'keywords' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'job_type' => ['nullable', Rule::in(array_keys(JobType::options()))],
            'min_match' => ['required', 'integer', 'min:0', 'max:100'],
            'frequency' => ['required', Rule::in(['instant', 'daily'])],
        ]);

        if ($user->jobAlerts()->count() >= self::MAX_ALERTS) {
            throw ValidationException::withMessages([
                'keywords' => 'You can have up to '.self::MAX_ALERTS.' alerts. Delete one to add another.',
            ]);
        }

        $user->jobAlerts()->create($data + ['is_active' => true]);

        return back()->with('status', 'Alert created. We will tell you when a matching job is posted.');
    }

    public function toggle(Request $request, JobAlert $alert): RedirectResponse
    {
        abort_unless($alert->user_id === $request->user()->id, 403);

        $alert->update(['is_active' => ! $alert->is_active]);

        return back()->with('status', $alert->is_active ? 'Alert turned on.' : 'Alert paused.');
    }

    public function destroy(Request $request, JobAlert $alert): RedirectResponse
    {
        abort_unless($alert->user_id === $request->user()->id, 403);

        $alert->delete();

        return back()->with('status', 'Alert deleted.');
    }
}
