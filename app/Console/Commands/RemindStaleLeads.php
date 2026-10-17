<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\StaleLeadsReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class RemindStaleLeads extends Command
{
    protected $signature = 'talenthub:remind-stale-leads';

    protected $description = 'Notify admins about new leads nobody has contacted yet';

    public function handle(): int
    {
        $hours = (int) config('talenthub.leads.stale_after_hours', 48);

        $stale = Lead::where('stage', 'new')
            ->where('created_at', '<', now()->subHours($hours));

        $count = (clone $stale)->count();

        if ($count === 0) {
            $this->info('No stale leads.');

            return self::SUCCESS;
        }

        $top = (clone $stale)->orderByDesc('score')->limit(3)->pluck('name')->all();

        $admins = User::where('role', Role::Admin->value)->where('is_active', true)->get();
        Notification::send($admins, new StaleLeadsReminder($count, $hours, $top));

        $this->info("Reminded {$admins->count()} admin(s) about {$count} stale lead(s).");

        return self::SUCCESS;
    }
}
