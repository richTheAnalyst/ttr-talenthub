<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Placement;
use App\Models\PlacementPayment;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Month by month: did the employer pay TTR, and has TTR paid the worker? */
class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $month = $this->month($request->query('month'));
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $placements = Placement::where('engagement', '!=', 'recruitment')
            ->with(['candidate:id,name', 'employer:id,company_name'])
            ->get()
            ->filter(function (Placement $p) use ($start, $end) {
                $from = $p->starts_on ?? $p->created_at;

                return $from <= $end && ($p->ends_on === null || $p->ends_on >= $start);
            })
            ->values();

        $payments = PlacementPayment::whereDate('period', $start->toDateString())
            ->whereIn('placement_id', $placements->pluck('id'))
            ->get()->keyBy('placement_id');

        $rows = $placements->map(function (Placement $p) use ($payments) {
            $pay = $payments->get($p->id);
            $expectedWorker = (int) ($p->monthly_salary ?? 0);
            $expectedClient = (int) ($p->client_monthly ?? ($expectedWorker + (int) round($p->monthlyFee() ?? 0)));

            return [
                'placement' => $p,
                'payment' => $pay,
                'expected_client' => $expectedClient,
                'expected_worker' => $expectedWorker,
            ];
        });

        // Totals per currency for the month.
        $summary = $rows->groupBy(fn ($r) => $r['placement']->currency)->map(fn ($g) => [
            'expected' => $g->sum('expected_client'),
            'received' => $g->sum(fn ($r) => $r['payment']?->client_paid_at ? ($r['payment']->client_amount ?? $r['expected_client']) : 0),
            'owed_workers' => $g->sum(fn ($r) => ($r['payment']?->client_paid_at && ! $r['payment']?->worker_paid_at) ? $r['expected_worker'] : 0),
            'paid_workers' => $g->sum(fn ($r) => $r['payment']?->worker_paid_at ? ($r['payment']->worker_amount ?? $r['expected_worker']) : 0),
        ]);

        return view('admin.payments', [
            'rows' => $rows,
            'summary' => $summary,
            'month' => $start,
            'prev' => $start->copy()->subMonth()->format('Y-m'),
            'next' => $start->copy()->addMonth()->format('Y-m'),
            'isCurrentOrFuture' => $start->greaterThanOrEqualTo(now()->startOfMonth()),
        ]);
    }

    public function update(Request $request, Placement $placement): RedirectResponse
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'client_amount' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'worker_amount' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'notes' => ['nullable', 'string', 'max:300'],
        ]);

        $period = Carbon::createFromFormat('Y-m-d', $data['month'].'-01')->startOfMonth();
        $existing = PlacementPayment::where('placement_id', $placement->id)->whereDate('period', $period->toDateString())->first();

        $payment = PlacementPayment::updateOrCreate(
            ['placement_id' => $placement->id, 'period' => $period->toDateString()],
            [
                'client_amount' => $data['client_amount'] ?? null,
                'client_paid_at' => $request->boolean('client_paid') ? ($existing?->client_paid_at ?? now()) : null,
                'worker_amount' => $data['worker_amount'] ?? null,
                'worker_paid_at' => $request->boolean('worker_paid') ? ($existing?->worker_paid_at ?? now()) : null,
                'notes' => $data['notes'] ?? null,
            ]
        );

        $warn = $payment->worker_paid_at && ! $payment->client_paid_at
            ? ' Note: the worker is marked paid but the employer has not paid yet.'
            : '';

        return redirect()->route('admin.payments.index', ['month' => $data['month']])
            ->with('status', 'Payment saved.'.$warn);
    }

    private function month(?string $value): Carbon
    {
        try {
            return $value ? Carbon::createFromFormat('Y-m-d', $value.'-01')->startOfMonth() : now()->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }
}