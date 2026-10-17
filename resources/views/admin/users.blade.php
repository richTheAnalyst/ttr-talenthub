@extends('layouts.admin')
@section('title', 'Users')
@section('content')
    <x-page-header title="Users" />
    <form method="GET" class="card mb-5 grid gap-3 sm:grid-cols-[1fr_11rem_auto]" role="search">
        <div><label for="q" class="label">Search</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Name or email" type="search"></div>
        <x-select name="role" label="Role" :options="\App\Enums\Role::options()" :value="$filters['role'] ?? null" placeholder="All roles" />
        <div class="flex items-end gap-2"><button class="btn-primary" type="submit">Filter</button>@if (array_filter($filters))<a href="{{ route('admin.users.index') }}" class="btn-ghost no-underline">Clear</a>@endif</div>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Name</th><th>Role</th><th>Joined</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
                @foreach ($users as $u)
                    <tr>
                        <td><p class="font-medium text-ink-900">{{ $u->name }}</p><p class="text-xs text-ink-500">{{ $u->email }}</p></td>
                        <td>{{ $u->role->label() }}</td>
                        <td class="whitespace-nowrap">{{ $u->created_at->format('j M Y') }}</td>
                        <td><x-badge :color="$u->is_active ? 'green' : 'red'">{{ $u->is_active ? 'Active' : 'Deactivated' }}</x-badge></td>
                        <td class="text-right">
                            @if ($u->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.toggle', $u) }}" data-confirm="{{ $u->is_active ? 'Deactivate '.$u->name.'? They will be signed out.' : 'Reactivate '.$u->name.'?' }}">@csrf @method('PATCH')<button class="{{ $u->is_active ? 'btn-danger' : 'btn-ghost' }} btn-sm" type="submit">{{ $u->is_active ? 'Deactivate' : 'Reactivate' }}</button></form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @include('partials.pager', ['p' => $users])
@endsection
