<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Placement;
use Carbon\Carbon;
use Illuminate\View\View;

/** What TTR earns: monthly outsourcing income plus one-time recruitment fees, per currency. */
class EarningsController extends Controller
{
    public function index(): View
    {
        $placements = Placement::with('employer:id,company_name')->get();
        $now = now();
        $months = collect(range(5, 0))->map(fn ($i) => $now->copy()->startOfMonth()->subMonths($i));

        $report = [];

        foreach ($placements->groupBy('currency') as $currency => $group) {
            $outsourcing = $group->where('engagement', '!=', 'recruitment');
            $recruitment = $group->where('engagement', 'recruitment');

            $series = $months->map(function (Carbon $m) use ($outsourcing, $recruitment) {
                $start = $m->copy()->startOfMonth();
                $end = $m->copy()->endOfMonth();

                // Outsourcing: a placement earns for every month it was running.
                $monthly = $outsourcing->filter(function (Placement $p) use ($start, $end) {
                    $from = $p->starts_on ?? $p->created_at;
                    $to = $p->ends_on;

                    return $from <= $end && ($to === null || $to >= $start);
                })->sum(fn (Placement $p) => $p->monthlyFee() ?? 0);

                // Recruitment: counted in the month the fee was paid.
                $fees = $recruitment->filter(fn (Placement $p) => $p->fee_paid_at && $p->fee_paid_at->between($start, $end))
                    ->sum('one_time_fee');

                return ['label' => $m->format('M'), 'outsourcing' => (float) $monthly, 'recruitment' => (float) $fees];
            })->values()->all();

            $employers = $group->groupBy('employer_id')->map(function ($rows) {
                return [
                    'name' => $rows->first()->employer?->company_name ?? 'Unknown',
                    'monthly' => $rows->where('status', 'active')->where('engagement', '!=', 'recruitment')->sum(fn (Placement $p) => $p->monthlyFee() ?? 0),
                    'paid' => $rows->where('engagement', 'recruitment')->whereNotNull('fee_paid_at')->sum('one_time_fee'),
                    'due' => $rows->where('engagement', 'recruitment')->whereNull('fee_paid_at')->sum('one_time_fee'),
                ];
            })->sortByDesc(fn ($e) => $e['monthly'] + $e['paid'] + $e['due'])->take(8)->values();

            $thisMonth = end($series);

            $report[$currency] = [
                'recurring' => $outsourcing->where('status', 'active')->sum(fn (Placement $p) => $p->monthlyFee() ?? 0),
                'active' => $outsourcing->where('status', 'active')->count(),
                'this_month' => $thisMonth['outsourcing'] + $thisMonth['recruitment'],
                'fees_paid' => $recruitment->whereNotNull('fee_paid_at')->sum('one_time_fee'),
                'fees_due' => $recruitment->whereNull('fee_paid_at')->sum('one_time_fee'),
                'series' => $series,
                'max' => max(1, collect($series)->max(fn ($s) => $s['outsourcing'] + $s['recruitment'])),
                'employers' => $employers,
            ];
        }

        return view('admin.earnings', ['report' => $report]);
    }
}