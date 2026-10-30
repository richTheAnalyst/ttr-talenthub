@extends('layouts.admin')
@section('title', 'Placements')
@section('content')
    <x-page-header title="Placements" subtitle="Every hire made through TTR and the monthly commission it earns." />

    @if ($recruitment->isNotEmpty())
        <div class="mb-4 grid gap-3 sm:grid-cols-2">
            @foreach ($recruitment as $currency => $t)
                <div class="card">
                    <p class="text-sm text-ink-500">Recruitment fees, {{ $currency }}</p>
                    <p class="text-3xl font-bold text-ink-900">{{ number_format($t['due']) }} <span class="text-base font-medium text-ink-500">due</span></p>
                    <p class="text-xs text-ink-500">{{ number_format($t['paid']) }} paid · {{ $t['count'] }} {{ \Illuminate\Support\Str::plural('placement', $t['count']) }}</p>
                </div>
            @endforeach
        </div>
    @endif

    @if ($totals->isEmpty())
        <p class="mb-4 text-sm text-ink-600">No active placements yet. One is created automatically when an application is marked Hired.</p>
    @else
        <div class="mb-6 grid gap-3 sm:grid-cols-2">
            @foreach ($totals as $currency => $t)
                <div class="card">
                    <p class="text-sm text-ink-500">Outsourcing income each month, {{ $currency }}</p>
                    <p class="text-3xl font-bold text-ink-900">{{ number_format($t['fee'], 2) }}</p>
                    <p class="text-xs text-ink-500">{{ $t['count'] }} active {{ \Illuminate\Support\Str::plural('placement', $t['count']) }} · {{ number_format($t['salary']) }} total monthly salary</p>
                </div>
            @endforeach
        </div>
    @endif

    @if ($placements->isEmpty())
        <x-empty title="No placements" icon="users">Hires will be listed here.</x-empty>
    @else
        <ul class="space-y-3">
            @foreach ($placements as $p)
                <li class="card">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-ink-900">{{ $p->candidate->name }}</p>
                            <p class="text-sm text-ink-600">{{ $p->application?->job?->title }} at {{ $p->employer->company_name }} · <x-badge :color="$p->isRecruitment() ? 'violet' : 'blue'">{{ $p->isRecruitment() ? 'Recruitment' : 'Outsourcing' }}</x-badge></p>
                        </div>
                        <div class="text-right">
                            <x-badge :color="$p->status === 'active' ? 'green' : 'slate'">{{ ucfirst($p->status) }}</x-badge>
                            <p class="mt-1 text-sm font-semibold text-ink-900">
                                @if ($p->isRecruitment())
                                    {{ $p->one_time_fee !== null ? $p->currency.' '.number_format($p->one_time_fee).' one-time, '.($p->fee_paid_at ? 'paid' : 'due') : 'Set the fee' }}
                                @else
                                    {{ $p->monthlyFee() !== null ? $p->currency.' '.number_format($p->monthlyFee(), 2).' a month' : 'Set the salary' }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.placements.update', $p) }}" class="mt-3 grid gap-3 border-t border-ink-100 pt-3 sm:grid-cols-3">
                        @csrf @method('PATCH')
                        <div>
                            <label class="label" for="sal-{{ $p->id }}">Worker's monthly pay</label>
                            <input class="input" id="sal-{{ $p->id }}" name="monthly_salary" type="number" min="0" value="{{ $p->monthly_salary }}">
                        </div>
                        @if ($p->isRecruitment())
                            <div>
                                <label class="label" for="fee-{{ $p->id }}">One-time fee</label>
                                <input class="input" id="fee-{{ $p->id }}" name="one_time_fee" type="number" min="0" value="{{ $p->one_time_fee }}">
                            </div>
                            <label class="flex items-center gap-2 pt-6 text-sm"><input type="checkbox" name="fee_paid" value="1" @checked($p->fee_paid_at)> Fee paid by the employer</label>
                        @else
                            <div>
                                <label class="label" for="cm-{{ $p->id }}">Client pays TTR each month</label>
                                <input class="input" id="cm-{{ $p->id }}" name="client_monthly" type="number" min="0" value="{{ $p->client_monthly }}">
                            </div>
                        @endif
                        <div>
                            <label class="label" for="cur-{{ $p->id }}">Currency</label>
                            <input class="input" id="cur-{{ $p->id }}" name="currency" maxlength="3" value="{{ $p->currency }}" required>
                        </div>
                        <div>
                            <label class="label" for="rate-{{ $p->id }}">Commission %</label>
                            <input class="input" id="rate-{{ $p->id }}" name="commission_rate" type="number" step="0.01" min="0" max="100" value="{{ $p->commission_rate }}" required>
                        </div>
                        <div>
                            <label class="label" for="st-{{ $p->id }}">Starts</label>
                            <input class="input" id="st-{{ $p->id }}" name="starts_on" type="date" value="{{ $p->starts_on?->format('Y-m-d') }}">
                        </div>
                        <div>
                            <label class="label" for="en-{{ $p->id }}">Ended</label>
                            <input class="input" id="en-{{ $p->id }}" name="ends_on" type="date" value="{{ $p->ends_on?->format('Y-m-d') }}">
                        </div>
                        <div>
                            <label class="label" for="stat-{{ $p->id }}">Status</label>
                            <select class="input" id="stat-{{ $p->id }}" name="status">
                                <option value="active" @selected($p->status === 'active')>Active</option>
                                <option value="ended" @selected($p->status === 'ended')>Ended</option>
                            </select>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="label" for="nt-{{ $p->id }}">Notes (private)</label>
                            <input class="input" id="nt-{{ $p->id }}" name="notes" maxlength="1000" value="{{ $p->notes }}">
                        </div>
                        <div class="sm:col-span-3"><button class="btn-primary btn-sm" type="submit">Save</button></div>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif
@endsection