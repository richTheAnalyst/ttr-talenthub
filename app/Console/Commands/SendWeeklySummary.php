<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\WeeklySummaryReport;
use App\Services\WeeklySummary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class SendWeeklySummary extends Command
{
    protected $signature = 'talenthub:weekly-summary';

    protected $description = 'Send admins the last 7 days of platform activity';

    public function handle(WeeklySummary $summary): int
    {
        Cache::forget('weekly-summary');

        $admins = User::where('role', Role::Admin->value)->where('is_active', true)->get();
        Notification::send($admins, new WeeklySummaryReport($summary->build(), $summary->staleLeads()));

        $this->info("Weekly summary sent to {$admins->count()} admin(s).");

        return self::SUCCESS;
    }
}