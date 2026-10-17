{{-- Previous / Next for simplePaginate(). @include('partials.pager', ['p' => $jobs]) --}}
@if ($p->hasPages())
    <nav class="mt-5 flex items-center justify-between gap-3" aria-label="Pagination">
        @if ($p->onFirstPage())
            <span class="btn-ghost btn-sm pointer-events-none opacity-50">Previous</span>
        @else
            <a href="{{ $p->previousPageUrl() }}" rel="prev" class="btn-ghost btn-sm no-underline">Previous</a>
        @endif
        <span class="muted">Page {{ $p->currentPage() }}</span>
        @if ($p->hasMorePages())
            <a href="{{ $p->nextPageUrl() }}" rel="next" class="btn-ghost btn-sm no-underline">Next</a>
        @else
            <span class="btn-ghost btn-sm pointer-events-none opacity-50">Next</span>
        @endif
    </nav>
@endif
