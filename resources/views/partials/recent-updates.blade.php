{{-- Latest notifications for the signed-in user, shown on dashboards. --}}
@php $updates = auth()->user()->notifications()->latest()->limit(5)->get(); @endphp
@if ($updates->isNotEmpty())
    <section class="mb-6" aria-labelledby="updates">
        <div class="mb-2 flex items-end justify-between">
            <h2 id="updates" class="text-lg font-bold">Latest updates</h2>
            <a href="{{ route('notifications.index') }}" class="text-sm font-semibold">See all</a>
        </div>
        <ul class="card-flat divide-y divide-ink-100 overflow-hidden">
            @foreach ($updates as $n)
                <li><a href="{{ route('notifications.read', $n->id) }}" class="flex items-start gap-3 px-4 py-3 no-underline hover:bg-ink-50">
                    <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $n->read_at ? 'bg-ink-200' : 'bg-gold-500' }}"></span>
                    <span class="min-w-0"><span class="block text-sm font-semibold text-ink-900">{{ $n->data['title'] ?? 'Update' }}</span><span class="block whitespace-pre-line text-sm text-ink-600">{{ $n->data['body'] ?? '' }}</span><span class="block text-xs text-ink-400">{{ $n->created_at->diffForHumans() }}</span></span>
                </a></li>
            @endforeach
        </ul>
    </section>
@endif