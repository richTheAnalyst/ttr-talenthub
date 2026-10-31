@extends('layouts.admin')
@section('title', 'Earnings')
@section('content')
    <x-page-header title="Earnings" subtitle="What TTR earns from outsourcing each month and from one-time recruitment fees." />

    @if (empty($report))
        <x-empty title="No earnings yet" icon="chart">Earnings appear here once an application is marked Hired and the placement has its amounts filled in.</x-empty>
    @endif

    @foreach ($report as $currency => $r)
        <section class="mb-8">
            <h2 class="mb-3 text-lg font-semibold">{{ $currency }}</h2>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="card">
                    <p class="text-sm text-ink-500">Earned this month</p>
                    <p class="text-3xl font-bold text-ink-900">{{ number_format($r['this_month'], 2) }}</p>
                    <p class="text-xs text-ink-500">Outsourcing plus fees paid</p>
                </div>
                <div class="card">
                    <p class="text-sm text-ink-500">Monthly outsourcing income</p>
                    <p class="text-3xl font-bold text-ink-900">{{ number_format($r['recurring'], 2) }}</p>
                    <p class="text-xs text-ink-500">{{ $r['active'] }} active {{ \Illuminate\Support\Str::plural('placement', $r['active']) }}</p>
                </div>
                <div class="card">
                    <p class="text-sm text-ink-500">Recruitment fees collected</p>
                    <p class="text-3xl font-bold text-ink-900">{{ number_format($r['fees_paid']) }}</p>
                    <p class="text-xs text-ink-500">All time</p>
                </div>
                <div class="card">
                    <p class="text-sm text-ink-500">Recruitment fees still due</p>
                    <p class="text-3xl font-bold {{ $r['fees_due'] > 0 ? 'text-amber-700' : 'text-ink-900' }}">{{ number_format($r['fees_due']) }}</p>
                    <p class="text-xs text-ink-500"><a href="{{ route('admin.placements.index') }}">Mark as paid in Placements</a></p>
                </div>
            </div>

            <div class="card mt-3">
                <h3 class="text-base font-semibold">Last 6 months</h3>
                <div class="mt-4 flex h-40 items-end gap-2" role="img" aria-label="Earnings by month in {{ $currency }}">
                    @foreach ($r['series'] as $s)
                        @php
                            $total = $s['outsourcing'] + $s['recruitment'];
                            $oPct = round($s['outsourcing'] / $r['max'] * 100);
                            $rPct = round($s['recruitment'] / $r['max'] * 100);
                        @endphp
                        <div class="flex h-full flex-1 flex-col items-center justify-end gap-1" title="{{ $s['label'] }}: {{ number_format($total, 2) }}">
                            <span class="text-[10px] text-ink-500">{{ $total > 0 ? number_format($total) : '' }}</span>
                            <div class="flex w-full flex-col justify-end" style="height: {{ max(2, $oPct + $rPct) }}%">
                                @if ($rPct) <div class="w-full rounded-t bg-amber-500" style="height: {{ $rPct / max(1, $oPct + $rPct) * 100 }}%"></div> @endif
                                <div class="w-full bg-cobalt-600 {{ $rPct ? '' : 'rounded-t' }}" style="height: {{ $oPct / max(1, $oPct + $rPct) * 100 }}%"></div>
                            </div>
                            <span class="text-xs text-ink-600">{{ $s['label'] }}</span>
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 flex gap-4 text-xs text-ink-600">
                    <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-cobalt-600"></span> Outsourcing (monthly)</span>
                    <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-amber-500"></span> Recruitment fees paid</span>
                </p>
            </div>

            <div class="card mt-3">
                <h3 class="text-base font-semibold">Top employers</h3>
                <div class="-mx-4 mt-2 overflow-x-auto px-4">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs text-ink-500"><tr><th class="py-2 pr-3 font-medium">Employer</th><th class="py-2 pr-3 font-medium">Monthly</th><th class="py-2 pr-3 font-medium">Fees paid</th><th class="py-2 font-medium">Fees due</th></tr></thead>
                        <tbody>
                            @foreach ($r['employers'] as $e)
                                <tr class="border-t border-ink-100">
                                    <td class="py-2 pr-3 font-medium text-ink-900">{{ $e['name'] }}</td>
                                    <td class="py-2 pr-3">{{ number_format($e['monthly'], 2) }}</td>
                                    <td class="py-2 pr-3">{{ number_format($e['paid']) }}</td>
                                    <td class="py-2">{{ number_format($e['due']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endforeach
@endsection