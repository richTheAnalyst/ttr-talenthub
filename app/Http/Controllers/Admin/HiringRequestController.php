<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\HiringRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HiringRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $requests = HiringRequest::with(['employer', 'assignee'])
            ->when($status && isset(HiringRequest::STATUSES[$status]), fn ($q) => $q->where('status', $status))
            ->latest()
            ->simplePaginate(15)
            ->withQueryString();

        return view('admin.hiring', [
            'requests' => $requests,
            'status' => $status,
            'rate' => (float) \App\Models\Setting::read('commission_rate', '15'),
            'admins' => User::where('role', Role::Admin->value)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, HiringRequest $hiringRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(HiringRequest::STATUSES))],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('role', Role::Admin->value)],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'placement_fee' => ['nullable', 'integer', 'min:0', 'max:100000000'],
        ]);

        $old = $hiringRequest->status;
        $hiringRequest->update($data);

        $changed = $old !== $hiringRequest->status;
        if ($changed) {
            $hiringRequest->loadMissing('employer.user');
            $hiringRequest->employer?->user?->notify(new \App\Notifications\HiringRequestUpdated($hiringRequest));
        }

        return back()->with('status', $changed ? 'Hiring request updated. The employer has been notified.' : 'Hiring request updated.');
    }
}