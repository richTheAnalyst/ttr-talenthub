@extends('layouts.admin')
@section('title', 'Payments')
@section('content')
    <x-page-header title="Payments" subtitle="Has the employer paid TTR, and has TTR paid the worker? Outsourcing only." />

    <div class="mb-4 flex items-center justify-between gap-3">
        <a href="{{ route('admin.payments.index', ['month' => $prev]) }}" class="btn-ghost btn-sm no-underline">&larr; Earlier</a>
        <h2 class="text-lg font-bold">{{ $month->format('F Y') }}</h2>
        <a href="{{ route('admin.payments.index', ['month' => $next]) }}" class="btn-ghost btn-sm no-underline">Later &rarr;</a>
    </div>

    @if ($summary->isNotEmpty())
        <div class="mb-5 grid gap-3 sm:grid-cols-2">
            @foreach ($summary as $currency => $s)
                <div class="card">
                    <p class="text-sm text-ink-500">{{ $currency }} this month</p>
                    <p class="text-2xl font-bold text-ink-900">{{ number_format($s['received']) }} <span class="text-base font-medium text-ink-500">of {{ number_format($s['expected']) }} received</span></p>
                    <p class="text-xs text-ink-600">
                        <span class="{{ $s['owed_workers'] > 0 ? 'font-semibold text-amber-700' : '' }}">{{ number_format($s['owed_workers']) }} to pay workers now</span>
                        · {{ number_format($s['paid_workers']) }} paid out
                    </p>
                </div>
            @endforeach
        </div>
    @endif

    @if ($rows->isEmpty())
        <x-empty title="No outsourcing placements this month" icon="users">Hired outsourcing workers appear here for each month they are working.</x-empty>
    @else
        <ul class="space-y-3">
            @foreach ($rows as $r)
                @php
                    $p = $r['placement'];
                    $pay = $r['payment'];
                    $late = $month->lt(now()->startOfMonth()) && ! $pay?->client_paid_at;
                @endphp
                <li class="card">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-ink-900">{{ $p->candidate->name }} <span class="font-normal text-ink-500">at {{ $p->employer->company_name }}</span></p>
                            <p class="text-xs text-ink-500">Employer pays {{ number_format($r['expected_client']) }} · worker gets {{ number_format($r['expected_worker']) }} · {{ $p->currency }}</p>
                        </div>
                        <div class="flex flex-wrap gap-1">
                            @if ($pay?->client_paid_at)<x-badge color="green">Employer paid</x-badge>@elseif ($late)<x-badge color="red">Employer late</x-badge>@else<x-badge color="amber">Awaiting employer</x-badge>@endif
                            @if ($pay?->worker_paid_at)<x-badge color="green">Worker paid</x-badge>@else<x-badge color="slate">Worker not paid</x-badge>@endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.payments.update', $p) }}" class="mt-3 grid gap-3 border-t border-ink-100 pt-3 sm:grid-cols-2">
                        @csrf @method('PATCH')
                        <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-medium"><input type="checkbox" name="client_paid" value="1" @checked($pay?->client_paid_at)> Employer has paid TTR</label>
                            <x-field name="client_amount" label="Amount received (optional)" type="number" min="0" :value="$pay?->client_amount" inputmode="numeric" hint="Leave empty if it matches {{ number_format($r['expected_client']) }}." />
                        </div>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-medium"><input type="checkbox" name="worker_paid" value="1" @checked($pay?->worker_paid_at)> TTR has paid the worker</label>
                            <x-field name="worker_amount" label="Amount paid (optional)" type="number" min="0" :value="$pay?->worker_amount" inputmode="numeric" hint="Leave empty if it matches {{ number_format($r['expected_worker']) }}." />
                        </div>
                        <x-field name="notes" label="Note (optional)" :value="$pay?->notes" class="sm:col-span-2" />
                        <div class="sm:col-span-2"><button class="btn-primary btn-sm" type="submit">Save</button></div>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif
@endsection