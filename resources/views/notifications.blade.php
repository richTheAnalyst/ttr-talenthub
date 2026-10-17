@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
    <x-page-header title="Notifications">
        @if (auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route('notifications.read-all') }}" data-allow-resubmit>@csrf<button class="btn-ghost btn-sm" type="submit">Mark all read</button></form>
        @endif
    </x-page-header>

    @if ($notifications->isEmpty())
        <x-empty title="You are all caught up" icon="bell">Updates on applications, matching jobs and leads will appear here.</x-empty>
    @else
        <ul class="card-flat divide-y divide-ink-100 overflow-hidden">
            @foreach ($notifications as $n)
                <li>
                    <a href="{{ route('notifications.read', $n->id) }}" class="flex items-start gap-3 px-4 py-3 no-underline hover:bg-ink-50">
                        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $n->read_at ? 'bg-transparent' : 'bg-gold-500' }}" aria-hidden="true"></span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm {{ $n->read_at ? 'font-medium text-ink-700' : 'font-semibold text-ink-900' }}">{{ $n->data['title'] ?? 'Update' }}@unless ($n->read_at)<span class="sr-only"> (unread)</span>@endunless</span>
                            <span class="block text-sm text-ink-600">{{ $n->data['body'] ?? '' }}</span>
                            <span class="mt-0.5 block text-xs text-ink-400">{{ $n->created_at->diffForHumans() }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
        @include('partials.pager', ['p' => $notifications])
    @endif
@endsection
