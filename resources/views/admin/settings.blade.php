@extends('layouts.admin')
@section('title', 'Settings')
@section('content')
    <x-page-header title="Settings" />

    <form method="POST" action="{{ route('admin.settings.update') }}" class="card max-w-2xl space-y-4">
        @csrf @method('PUT')

        <div>
            <h2 class="text-base font-semibold text-ink-900">CV received</h2>
            <p class="mt-1 text-sm text-ink-500">Sent to a candidate (in-app notification and email) right after they upload a CV.</p>
        </div>

        <label class="flex items-center gap-3">
            <input type="checkbox" name="cv_ack_enabled" value="1" @checked(old('cv_ack_enabled', $enabled))>
            <span class="text-sm font-medium text-ink-900">Send this response automatically</span>
        </label>

        <div>
            <label for="cv_ack_subject" class="label">Subject</label>
            <input id="cv_ack_subject" name="cv_ack_subject" class="input" maxlength="150" value="{{ old('cv_ack_subject', $subject) }}" required>
            @error('cv_ack_subject')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="cv_ack_message" class="label">Message</label>
            <textarea id="cv_ack_message" name="cv_ack_message" rows="7" class="input" maxlength="2000" required>{{ old('cv_ack_message', $message) }}</textarea>
            <p class="mt-1 text-xs text-ink-500">Use <code>{name}</code> to insert the candidate's name. Leave a blank line between paragraphs.</p>
            @error('cv_ack_message')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <hr class="border-ink-100">

        <div>
            <h2 class="text-base font-semibold text-ink-900">Approvals and alerts</h2>
        </div>

        <label class="flex items-start gap-3">
            <input type="checkbox" name="interview_approval" value="1" class="mt-1" @checked(old('interview_approval', $interviewApproval))>
            <span class="text-sm text-ink-900"><span class="font-medium">Approve interviews before the candidate sees them.</span><br><span class="text-ink-500">When on, an interview an employer proposes waits on the Approvals page. Interviews you schedule yourself are never held.</span></span>
        </label>

        <label class="flex items-start gap-3">
            <input type="checkbox" name="admin_activity_alerts" value="1" class="mt-1" @checked(old('admin_activity_alerts', $activityAlerts))>
            <span class="text-sm text-ink-900"><span class="font-medium">Alert me about employer and candidate activity.</span><br><span class="text-ink-500">Status changes, interview proposals, confirmations and declines. Items that need your decision always alert you.</span></span>
        </label>

        <div>
            <label for="commission_rate" class="label">Outsourcing percentage TTR keeps (% of the client's budget)</label>
            <input id="commission_rate" name="commission_rate" type="number" step="0.01" min="0" max="100" class="input max-w-[10rem]" value="{{ old('commission_rate', $commissionRate) }}" required>
            <p class="mt-1 text-xs text-ink-500">Taken off the client's monthly budget when an outsourcing job is approved. The worker sees the rest as their pay.</p>
            @error('commission_rate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <button class="btn-primary" type="submit">Save</button>
    </form>
@endsection