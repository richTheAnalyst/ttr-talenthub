<?php

namespace App\Jobs;

use App\Models\JobAlert;
use App\Models\JobPosting;
use App\Notifications\DailyJobDigest;
use App\Services\MatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Scheduled every morning: one email per candidate listing the jobs published
 * in the last 24 hours that satisfy their daily alerts.
 */
class SendDailyAlertDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function handle(MatchingService $matching): void
    {
        $jobs = JobPosting::open()
            ->where('published_at', '>=', now()->subDay())
            ->with(['skills', 'employer'])
            ->get();

        if ($jobs->isEmpty()) {
            return;
        }

        JobAlert::active()
            ->where('frequency', 'daily')
            ->with('user.candidateProfile.skills')
            ->get()
            ->groupBy('user_id')
            ->each(function ($alerts) use ($jobs, $matching) {
                $user = $alerts->first()->user;

                if (! $user || ! $user->is_active) {
                    return;
                }

                $picked = [];

                foreach ($alerts as $alert) {
                    foreach ($jobs as $job) {
                        if (isset($picked[$job->id]) || ! $alert->matchesFilters($job)) {
                            continue;
                        }

                        $score = $user->candidateProfile ? $matching->scoreFor($user->candidateProfile, $job)['score'] : 0;

                        if ($score >= $alert->min_match) {
                            $picked[$job->id] = [
                                'id' => $job->id,
                                'title' => $job->title,
                                'company' => $job->employer->company_name,
                                'location' => $job->location,
                                'score' => $score,
                            ];
                        }
                    }
                }

                if ($picked) {
                    usort($picked, fn ($a, $b) => $b['score'] <=> $a['score']);
                    $user->notify(new DailyJobDigest(array_values($picked)));
                    JobAlert::whereIn('id', $alerts->pluck('id'))->update(['last_sent_at' => now()]);
                }
            });
    }
}
