@extends('layouts.app')
@section('title', 'My CVs')
@section('content')
    <x-page-header title="My CVs" :subtitle="'Keep up to '.$max.' CVs. Your primary CV is sent with new applications.'" />

    <form method="POST" action="{{ route('candidate.cvs.store') }}" enctype="multipart/form-data" class="card mb-5">
        @csrf
        <label for="cv" class="label">Upload a CV</label>
        <input id="cv" name="cv" type="file" accept=".pdf,.doc,.docx,application/pdf" required data-max-kb="{{ config('talenthub.cv.max_kb') }}" data-output="cv-picked"
               class="block w-full text-sm file:mr-3 file:min-h-[44px] file:rounded-xl file:border-0 file:bg-cobalt-50 file:px-4 file:font-semibold file:text-cobalt-700">
        <p id="cv-picked" class="hint">PDF, DOC or DOCX, up to {{ (int) (config('talenthub.cv.max_kb') / 1024) }} MB.</p>
        @error('cv')<p class="error">{{ $message }}</p>@enderror
        <button class="btn-primary mt-3" type="submit" @disabled($cvs->count() >= $max)><x-icon name="upload" class="h-5 w-5"/> Upload</button>
        @if ($cvs->count() >= $max)<p class="hint">You have reached the limit. Delete a CV to upload another.</p>@endif
    </form>

    @if ($cvs->isEmpty())
        <x-empty title="No CV yet" icon="doc">Upload your CV to apply for jobs and let employers find you.</x-empty>
    @else
        <ul class="space-y-3">
            @foreach ($cvs as $cv)
                <li class="card">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cobalt-50 text-cobalt-600"><x-icon name="doc"/></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-ink-900">{{ $cv->original_name }}</p>
                            <p class="text-xs text-ink-500">{{ $cv->humanSize() }} · uploaded {{ $cv->created_at->format('j M Y') }}</p>
                        </div>
                        @if ($cv->is_primary)<x-badge color="gold">Primary</x-badge>@endif
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('cv.download', $cv) }}" class="btn-ghost btn-sm no-underline">Download</a>
                        @unless ($cv->is_primary)
                            <form method="POST" action="{{ route('candidate.cvs.primary', $cv) }}">@csrf<button class="btn-ghost btn-sm" type="submit">Make primary</button></form>
                        @endunless
                        <form method="POST" action="{{ route('candidate.cvs.destroy', $cv) }}" data-confirm="Delete this CV?">@csrf @method('DELETE')<button class="btn-danger btn-sm" type="submit">Delete</button></form>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
