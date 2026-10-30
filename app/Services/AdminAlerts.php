<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AdminActivity;
use Illuminate\Support\Facades\Notification;

class AdminAlerts
{
    /**
     * Tell every active admin about something. Respects the "activity alerts" setting
     * unless $force is true (used for things that need an admin decision).
     */
    public static function notify(string $title, string $body, ?string $url = null, bool $force = false): void
    {
        try {
            if (! $force && Setting::read('admin_activity_alerts', '1') !== '1') {
                return;
            }

            $admins = User::where('role', Role::Admin->value)->where('is_active', true)->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new AdminActivity($title, $body, $url));
            }
        } catch (\Throwable $e) {
            // An alert must never break the action that triggered it.
            report($e);
        }
    }
}