<?php

namespace App\Services;

/**
 * Scores a lead 0 to 100 so the sales team can work the warmest ones first.
 * Pure function over plain values: no database or framework access.
 */
class LeadScorer
{
    private const BASE = [
        'job_seeker' => 30,
        'study_abroad' => 35,
        'employer' => 40,
    ];

    /**
     * @param  array{has_email?:bool,has_phone?:bool,registered?:bool,completeness?:int,has_cv?:bool,consent_marketing?:bool,destination?:?string,study_level?:?string,urgency?:?string,headcount?:int,source?:?string}  $signals
     */
    public function score(string $type, array $signals = []): int
    {
        $score = self::BASE[$type] ?? 25;

        $score += ! empty($signals['has_email']) ? 5 : 0;
        $score += ! empty($signals['has_phone']) ? 10 : 0;
        $score += ! empty($signals['registered']) ? 10 : 0;
        $score += ! empty($signals['has_cv']) ? 10 : 0;
        $score += ! empty($signals['consent_marketing']) ? 5 : 0;
        $score += (int) round(min(100, max(0, (int) ($signals['completeness'] ?? 0))) / 100 * 15);

        if ($type === 'study_abroad') {
            $score += ! empty($signals['destination']) ? 10 : 0;
            $score += ! empty($signals['study_level']) ? 5 : 0;
        }

        if ($type === 'employer') {
            $score += match ($signals['urgency'] ?? 'normal') {
                'high' => 15,
                'normal' => 5,
                default => 0,
            };

            $headcount = (int) ($signals['headcount'] ?? 1);
            $score += $headcount >= 5 ? 10 : ($headcount >= 2 ? 5 : 0);
        }

        if (($signals['source'] ?? null) === 'referral') {
            $score += 5;
        }

        return max(0, min(100, $score));
    }
}
