<?php

use App\Jobs\SendDailyAlertDigest;
use App\Models\JobPosting;
use Illuminate\Support\Facades\Schedule;

// Morning digest for candidates with "daily" job alerts (Ghana is on UTC all year).
Schedule::job(new SendDailyAlertDigest)->dailyAt('07:00')->name('daily-alert-digest');

// Close jobs whose closing date has passed.
Schedule::call(function () {
    JobPosting::where('status', JobPosting::STATUS_OPEN)
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->update(['status' => JobPosting::STATUS_CLOSED]);
})->hourly()->name('close-expired-jobs');

// Nudge admins about leads nobody has contacted.
Schedule::command('talenthub:remind-stale-leads')->dailyAt('08:00');

// Remind both sides about confirmed interviews in the next 24 hours.
Schedule::command('talenthub:interview-reminders')->everyTenMinutes();

// Monday recap for admins (UTC).
Schedule::command('talenthub:weekly-summary')->weeklyOn(1, '08:00');

// Housekeeping.
Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::command('auth:clear-resets')->daily();