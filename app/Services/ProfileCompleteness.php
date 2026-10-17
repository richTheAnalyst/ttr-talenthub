<?php

namespace App\Services;

/**
 * How complete a candidate profile is, as a percentage. Pure function.
 */
class ProfileCompleteness
{
    /**
     * @param  array<string,mixed>  $profile  candidate_profiles columns
     */
    public static function calculate(array $profile, int $skillCount, bool $hasCv, ?string $phone): int
    {
        $score = 0;

        $score += self::filled($profile['headline'] ?? null) ? 10 : 0;
        $score += self::filled($profile['location'] ?? null) ? 10 : 0;
        $score += $skillCount >= 3 ? 20 : ($skillCount > 0 ? 10 : 0);
        $score += self::filled($profile['education_level'] ?? null) ? 10 : 0;
        $score += ((int) ($profile['years_experience'] ?? 0)) > 0 ? 5 : 0;
        $score += self::filled($profile['desired_title'] ?? null) ? 10 : 0;
        $score += $hasCv ? 20 : 0;
        $score += self::filled($phone) ? 5 : 0;
        $score += self::filled($profile['bio'] ?? null) ? 5 : 0;

        $interest = $profile['interest_type'] ?? 'job';
        $wantsStudy = in_array($interest, ['study_abroad', 'both'], true);
        $interestDone = $wantsStudy
            ? self::filled($profile['study_destination'] ?? null)
            : self::filled($profile['desired_job_type'] ?? null);
        $score += $interestDone ? 5 : 0;

        return min(100, $score);
    }

    private static function filled(mixed $value): bool
    {
        return $value !== null && trim((string) $value) !== '';
    }
}
