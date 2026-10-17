@extends('layouts.admin')
@section('title', 'Lead: '.$lead->name)
@section('content')
    <a href="{{ route('admin.leads.index') }}" class="mb-3 inline-flex items-center gap-1 text-sm font-medium"><x-icon name="chevron" class="h-4 w-4 rotate-180"/> Leads</a>
    <x-page-header :title="$lead->name" :subtitle="$lead->typeLabel().' lead, created '.$lead->created_at->format('j M Y')">
        <x-match-ring :score="$lead->score" size="52" />
        <x-status-badge :status="$lead->stage" />
    </x-page-header>

    <div class="grid gap-5 lg:grid-cols-[1fr_24rem]">
        <div class="space-y-5">
            <section class="card">
                <h2 class="text-base font-semibold">Contact</h2>
                <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-ink-500">Email</dt><dd>@if ($lead->email)<a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a>@else Not given @endif</dd></div>
                    <div><dt class="text-xs text-ink-500">Phone</dt><dd>@if ($lead->phone)<a href="tel:{{ $lead->phone }}">{{ $lead->phone }}</a>@else Not given @endif</dd></div>
                    <div><dt class="text-xs text-ink-500">Country</dt><dd>{{ $lead->country ?: 'Not given' }}</dd></div>
                    <div><dt class="text-xs text-ink-500">Source</dt><dd>{{ str_replace('_', ' ', $lead->source) }}</dd></div>
                    <div><dt class="text-xs text-ink-500">Last contacted</dt><dd>{{ $lead->last_contacted_at?->diffForHumans() ?? 'Never' }}</dd></div>
                    <div><dt class="text-xs text-ink-500">CRM sync</dt><dd>{{ $lead->crm_synced_at ? 'Synced '.$lead->crm_synced_at->diffForHumans() : (config('talenthub.crm.webhook_url') ? 'Pending' : 'Webhook not configured') }}</dd></div>
                    @if ($lead->user)<div><dt class="text-xs text-ink-500">Account</dt><dd>{{ $lead->user->email }}</dd></div>@endif
                </dl>
            </section>

            @if (! empty($lead->details))
                <section class="card">
                    <h2 class="text-base font-semibold">Details</h2>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                        @foreach ($lead->details as $key => $value)
                            <div><dt class="text-xs capitalize text-ink-500">{{ str_replace('_', ' ', $key) }}</dt><dd class="whitespace-pre-line">{{ is_scalar($value) ? $value : json_encode($value) }}</dd></div>
                        @endforeach
                    </dl>
                </section>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.leads.update', $lead) }}" class="card space-y-4 lg:self-start">
            @csrf @method('PUT')
            <h2 class="text-base font-semibold">Work this lead</h2>
            <x-select name="stage" label="Stage" :options="\App\Enums\LeadStage::options()" :value="$lead->stage->value" required />
            <x-select name="assigned_to" label="Assigned to" :options="$admins->pluck('name', 'id')->all()" :value="$lead->assigned_to" placeholder="Unassigned" />
            <x-textarea name="notes" label="Notes" :value="$lead->notes" rows="6" />
            <button class="btn-primary btn-block" type="submit">Save</button>
        </form>
    </div>
@endsection
