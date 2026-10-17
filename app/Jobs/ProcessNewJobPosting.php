<?php

namespace App\Jobs;

use App\Models\CandidateProfile;
use App\Models\JobAlert;
use App\Models\JobPosting;
use App\Notifications\JobAlertMatch;
use App\Services\MatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Runs when a job goes live. First it honours explicit instant alerts, then it
 * proactively tells strongly matching candidates (in-app only, capped).
 */
class ProcessNewJobPosting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public int $jobId)
    {
    }

    public function handle(MatchingService $matching): void
    {
        $job = JobPosting::with(['skills', 'employer'])->find($this->jobId);

        if (! $job || ! $job->isOpen()) {
            return;
        }

        $notified = [];

        // 1. Explicit instant alerts
        JobAlert::active()
            ->where('frequency', 'instant')
            ->with('user.candidateProfile.skills')
            ->chunkById(200, function ($alerts) use ($job, $matching, &$notified) {
                foreach ($alerts as $alert) {
                    $user = $alert->user;

                    if (! $user || ! $user->is_active || isset($notified[$user->id]) || ! $alert->matchesFilters($job)) {
                        continue;
                    }

                    $score = $user->candidateProfile ? $matching->scoreFor($user->candidateProfile, $job)['score'] : 0;
                    if ($score < $alert->min_match) {
                        continue;
                    }

                    $user->notify(new JobAlertMatch($job, $score, viaMail: true));
                    $alert->forceFill(['last_sent_at' => now()])->saveQuietly();
                    $notified[$user->id] = true;
                }
            });

        // 2. Proactive matches: strong fits who did not set up an alert
        $minScore = (int) config('talenthub.matching.proactive_min_score', 70);
        $cap = (int) config('talenthub.matching.proactive_max_per_job', 50);
        $sent = 0;

        CandidateProfile::searchable()
            ->wantsJobs()
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->with(['skills', 'user'])
            ->chunkById(200, function ($profiles) use ($job, $matching, $minScore, $cap, &$notified, &$sent) {
                foreach ($profiles as $profile) {
                    if ($sent >= $cap) {
                        return false;
                    }

                    if (isset($notified[$profile->user_id])) {
                        continue;
                    }

                    $score = $matching->scoreFor($profile, $job)['score'];
                    if ($score < $minScore) {
                        continue;
                    }

                    $profile->user->notify(new JobAlertMatch($job, $score, viaMail: false));
                    $notified[$profile->user_id] = true;
                    $sent++;
                }

                return true;
            });
    }
}
