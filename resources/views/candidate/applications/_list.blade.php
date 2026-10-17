<ul class="space-y-3">
    @foreach ($applications as $application)
        <li>
            <a href="{{ route('candidate.applications.show', $application) }}" class="card block no-underline transition hover:border-cobalt-300">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-ink-900">{{ $application->job->title }}</p>
                        <p class="truncate text-sm text-ink-600">{{ $application->job->employer->company_name }}</p>
                    </div>
                    <x-status-badge :status="$application->status" />
                </div>
                <p class="mt-2 text-xs text-ink-500">Applied {{ $application->created_at->diffForHumans() }} · updated {{ ($application->status_changed_at ?? $application->updated_at)->diffForHumans() }}</p>
            </a>
        </li>
    @endforeach
</ul>
