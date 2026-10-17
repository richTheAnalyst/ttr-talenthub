<?php

namespace App\Providers;

use App\Services\MatchingService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Cache;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MatchingService::class, fn () => new MatchingService(
            config('talenthub.matching.weights', MatchingService::DEFAULT_WEIGHTS)
        ));
    }

    public function boot(): void
    {
        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        // Public lead forms: a handful of submissions per minute per visitor.
        RateLimiter::for('forms', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));

        // Credential attempts: per email + IP so one attacker cannot lock out a victim from everywhere.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(6)->by(
            mb_strtolower((string) $request->input('email')).'|'.$request->ip()
        ));

               View::composer(['layouts.app', 'layouts.admin', 'layouts.employer'], function ($view) {
            $user = Auth::user();
            $view->with('unreadCount', $user
                ? Cache::remember('unread.'.$user->id, 60, fn () => $user->unreadNotifications()->count())
                : 0);
        });
    }
}
