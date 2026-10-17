<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('role:candidate') or 'role:employer,admin'.
 * Also stops deactivated accounts, on the web and on the API.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return $request->expectsJson() || $request->is('api/*')
                ? abort(401, 'Please sign in.')
                : redirect()->guest(route('login'));
        }

        if (! $user->is_active) {
            if ($request->expectsJson() || $request->is('api/*')) {
                abort(403, 'This account has been deactivated.');
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'This account has been deactivated. Contact support.']);
        }

        if (! in_array($user->role->value, $roles, true)) {
            abort(403, 'You do not have access to this area.');
        }

        return $next($request);
    }
}
