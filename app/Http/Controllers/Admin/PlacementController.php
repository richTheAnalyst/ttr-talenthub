<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Placement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Hires made through TTR and the monthly commission each one earns. */
class PlacementController extends Controller
{
    public function index(): View
    {
        $placements = Placement::with(['candidate:id,name', 'employer:id,company_name', 'application.job:id,title'])
            ->latest()
            ->get();

        // Outsourcing: monthly income still being earned, per currency.
        $totals = $placements->where('status', 'active')->where('engagement', '!=', 'recruitment')->groupBy('currency')->map(fn ($group) => [
            'count' => $group->count(),
            'salary' => $group->sum('monthly_salary'),
            'fee' => $group->sum(fn (Placement $p) => $p->monthlyFee() ?? 0),
        ]);

        // Recruitment: one-time fees, split into paid and still due.
        $recruitment = $placements->where('engagement', 'recruitment')->groupBy('currency')->map(fn ($group) => [
            'count' => $group->count(),
            'paid' => $group->whereNotNull('fee_paid_at')->sum('one_time_fee'),
            'due' => $group->whereNull('fee_paid_at')->sum('one_time_fee'),
        ]);

        return view('admin.placements', compact('placements', 'totals', 'recruitment'));
    }

    public function update(Request $request, Placement $placement): RedirectResponse
    {
        $data = $request->validate([
            'monthly_salary' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'client_monthly' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'one_time_fee' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'currency' => ['required', 'string', 'size:3'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'status' => ['required', Rule::in(['active', 'ended'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['currency'] = strtoupper($data['currency']);
        $data['fee_paid_at'] = $request->boolean('fee_paid') ? ($placement->fee_paid_at ?? now()) : null;

        if ($data['status'] === 'ended' && empty($data['ends_on'])) {
            $data['ends_on'] = now()->toDateString();
        }

        $placement->update($data);

        return back()->with('status', 'Placement saved.');
    }
}