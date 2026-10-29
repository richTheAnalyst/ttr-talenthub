@extends('layouts.admin')
@section('title', 'Automated responses')
@section('content')
    <x-page-header title="Automated responses" />

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

        <button class="btn-primary" type="submit">Save</button>
    </form>
@endsection