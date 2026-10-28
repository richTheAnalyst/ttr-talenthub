<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\Role;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\Cv;
use App\Models\HiringRequest;
use App\Models\JobPosting;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/** Last-7-days activity numbers, each with the change against the 7 days before. */
class WeeklySummary
{
    /** @return list<array{label:string,now:int,prev:int,delta:int}> */
    public function build(): array
    {
        return Cache::remember('weekly-summary', 600, function () {
            $metrics = [
                'New candidates' => fn () => User::where('role', Role::Candidate->value),
                'CVs uploaded' => fn () => Cv::query(),
                'Jobs posted' => fn () => JobPosting::query(),
                'Applications' => fn () => Application::query(),
                'Hires' => fn () => ApplicationStatusHistory::where('to_status', ApplicationStatus::Hired->value),
                'New leads' => fn () => Lead::query(),
                'Hiring requests' => fn () => HiringRequest::query(),
            ];

            $rows = [];
            foreach ($metrics as $label => $make) {
                $now = (int) $make()->where('created_at', '>=', now()->subDays(7))->count();
                $prev = (int) $make()->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count();
                $rows[] = ['label' => $label, 'now' => $now, 'prev' => $prev, 'delta' => $now - $prev];
            }

            return $rows;
        });
    }

    public function staleLeads(): int
    {
        return Lead::where('stage', 'new')
            ->where('created_at', '<', now()->subHours((int) config('talenthub.leads.stale_after_hours', 48)))
            ->count();
    }
}