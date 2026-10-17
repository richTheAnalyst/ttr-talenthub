<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Db;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only('role', 'q');
        $like = Db::like();

        $users = User::query()
            ->when(! empty($filters['role']) && Role::tryFrom($filters['role']), fn (Builder $q) => $q->where('role', $filters['role']))
            ->when(! empty($filters['q']), function (Builder $q) use ($filters, $like) {
                $term = Db::contains($filters['q']);
                $q->where(fn (Builder $w) => $w->where('name', $like, $term)->orWhere('email', $like, $term));
            })
            ->latest()
            ->simplePaginate(20)
            ->withQueryString();

        return view('admin.users', compact('users', 'filters'));
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'You cannot deactivate your own account.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        // Revoke API tokens and stop remembered sessions when an account is disabled.
        if (! $user->is_active) {
            $user->tokens()->delete();
            $user->forceFill(['remember_token' => null])->save();
        }

        return back()->with('status', $user->is_active ? "{$user->name} is active again." : "{$user->name} has been deactivated.");
    }
}
