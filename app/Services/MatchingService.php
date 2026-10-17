<?php

namespace App\Services;

use App\Models\CandidateProfile;
use App\Models\JobPosting;
use Illuminate\Support\Collection;

/**
 * Scores how well a candidate fits a job, 0 to 100.
 *
 * compute() is a pure function over plain arrays so it is easy to test and to
 * reuse from queue jobs, controllers and the API. The weights come from
 * config/talenthub.php and are normalised, so they do not have to add to 100.
 */
class MatchingService
{
    public const DEFAULT_WEIGHTS = [
        'skills' => 50,
        'title' => 15,
        'location' => 15,
        'experience' => 10,
        'job_type' => 5,
        'education' => 5,
    ];

    private const EDUCATION_RANK = [
        'secondary' => 1,
        'diploma' => 2,
        'bachelors' => 3,
        'masters' => 4,
        'phd' => 5,
    ];

    private const STOPWORDS = ['and', 'the', 'of', 'for', 'in', 'at', 'to', 'with', 'an', 'ii', 'iii'];

    public function __construct(private array $weights = self::DEFAULT_WEIGHTS)
    {
    }

    /**
     * @param  array{skills?:array,headline?:?string,desired_title?:?string,location?:?string,desired_location?:?string,country?:?string,years_experience?:int,education_level?:?string,desired_job_type?:?string}  $c  candidate
     * @param  array{title?:?string,location?:?string,country?:?string,experience_min?:int,education_level?:?string,job_type?:?string,skills_required?:array,skills_optional?:array}  $j  job
     * @return array{score:int,breakdown:array,matched_skills:list<string>,missing_skills:list<string>}
     */
    public function compute(array $c, array $j): array
    {
        $candidateSkills = $this->skillMap($c['skills'] ?? []);
        $required = $this->skillMap($j['skills_required'] ?? []);
        $optional = $this->skillMap($j['skills_optional'] ?? []);

        $matchedRequired = array_intersect_key($required, $candidateSkills);
        $matchedOptional = array_intersect_key($optional, $candidateSkills);
        $missing = array_diff_key($required, $candidateSkills);

        $ratios = [
            'skills' => $this->skillsRatio(count($required), count($optional), count($matchedRequired), count($matchedOptional)),
            'title' => $this->titleRatio($c, $j),
            'location' => $this->locationRatio($c, $j),
            'experience' => $this->experienceRatio($c, $j),
            'job_type' => $this->jobTypeRatio($c, $j),
            'education' => $this->educationRatio($c, $j),
        ];

        $totalWeight = array_sum($this->weights) ?: 1;
        $points = 0.0;
        $breakdown = [];

        foreach ($ratios as $key => $ratio) {
            $weight = $this->weights[$key] ?? 0;
            $breakdown[$key] = [
                'ratio' => round($ratio, 2),
                'points' => round($weight * $ratio, 1),
                'weight' => $weight,
            ];
            $points += $weight * $ratio;
        }

        return [
            'score' => (int) round(100 * $points / $totalWeight),
            'breakdown' => $breakdown,
            'matched_skills' => array_values($matchedRequired + $matchedOptional),
            'missing_skills' => array_values($missing),
        ];
    }

    public function scoreFor(CandidateProfile $profile, JobPosting $job): array
    {
        return $this->compute($profile->matchPayload(), $job->matchPayload());
    }

    /**
     * Rank jobs for one candidate, best first. Profile skills and each job's skills must be loaded.
     *
     * @param  iterable<JobPosting>  $jobs
     * @return Collection<int,array{job:JobPosting,score:int,breakdown:array,matched_skills:array,missing_skills:array}>
     */
    public function rankJobs(CandidateProfile $profile, iterable $jobs, int $minScore = 0, int $limit = 0): Collection
    {
        $payload = $profile->matchPayload();
        $rows = [];

        foreach ($jobs as $job) {
            $result = $this->compute($payload, $job->matchPayload());
            if ($result['score'] >= $minScore) {
                $rows[] = ['job' => $job] + $result;
            }
        }

        usort($rows, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return collect($limit > 0 ? array_slice($rows, 0, $limit) : $rows);
    }

    /**
     * Rank candidate profiles for one job, best first.
     *
     * @param  iterable<CandidateProfile>  $profiles
     * @return Collection<int,array{profile:CandidateProfile,score:int,breakdown:array,matched_skills:array,missing_skills:array}>
     */
    public function rankCandidates(JobPosting $job, iterable $profiles, int $minScore = 0, int $limit = 0): Collection
    {
        $payload = $job->matchPayload();
        $rows = [];

        foreach ($profiles as $profile) {
            $result = $this->compute($profile->matchPayload(), $payload);
            if ($result['score'] >= $minScore) {
                $rows[] = ['profile' => $profile] + $result;
            }
        }

        usort($rows, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return collect($limit > 0 ? array_slice($rows, 0, $limit) : $rows);
    }

    // ---- ratio helpers (each returns 0.0 to 1.0) -----------------------------

    private function skillsRatio(int $required, int $optional, int $matchedRequired, int $matchedOptional): float
    {
        if ($required && $optional) {
            return 0.8 * ($matchedRequired / $required) + 0.2 * ($matchedOptional / $optional);
        }
        if ($required) {
            return $matchedRequired / $required;
        }
        if ($optional) {
            return $matchedOptional / $optional;
        }

        return 0.5; // the job lists no skills: neither a fit nor a miss
    }

    private function titleRatio(array $c, array $j): float
    {
        $jobTokens = $this->tokens((string) ($j['title'] ?? ''));
        $candidateTokens = $this->tokens(($c['desired_title'] ?? '').' '.($c['headline'] ?? ''));

        if (! $jobTokens) {
            return 0.5;
        }
        if (! $candidateTokens) {
            return 0.3;
        }

        return min(1.0, count(array_intersect($jobTokens, $candidateTokens)) / count($jobTokens));
    }

    private function locationRatio(array $c, array $j): float
    {
        $jobLocation = $this->norm($j['location'] ?? '');

        if ($jobLocation === '' || str_contains($jobLocation, 'remote')) {
            return 1.0;
        }

        $known = false;
        foreach ([$c['location'] ?? '', $c['desired_location'] ?? ''] as $candidateLocation) {
            $candidateLocation = $this->norm($candidateLocation);
            if ($candidateLocation === '') {
                continue;
            }
            $known = true;
            if (str_contains($jobLocation, $candidateLocation) || str_contains($candidateLocation, $jobLocation)) {
                return 1.0;
            }
        }

        $jobCountry = $this->norm($j['country'] ?? '');
        $candidateCountry = $this->norm($c['country'] ?? '');
        if ($jobCountry !== '' && $jobCountry === $candidateCountry) {
            return 0.5;
        }

        return $known ? 0.0 : 0.4;
    }

    private function experienceRatio(array $c, array $j): float
    {
        $required = (int) ($j['experience_min'] ?? 0);
        $years = (int) ($c['years_experience'] ?? 0);

        if ($required <= 0 || $years >= $required) {
            return 1.0;
        }

        return $years / $required;
    }

    private function jobTypeRatio(array $c, array $j): float
    {
        $wanted = $c['desired_job_type'] ?? null;
        $offered = $j['job_type'] ?? null;

        if (! $wanted || ! $offered) {
            return 0.6;
        }

        return $wanted === $offered ? 1.0 : 0.0;
    }

    private function educationRatio(array $c, array $j): float
    {
        $required = self::EDUCATION_RANK[$j['education_level'] ?? ''] ?? 0;
        $has = self::EDUCATION_RANK[$c['education_level'] ?? ''] ?? 0;

        if ($required === 0) {
            return 1.0;
        }
        if ($has === 0) {
            return 0.5;
        }
        if ($has >= $required) {
            return 1.0;
        }

        return ($required - $has) === 1 ? 0.5 : 0.0;
    }

    // ---- text helpers -------------------------------------------------------

    /** @return array<string,string> lowercase key => original name */
    private function skillMap(array $skills): array
    {
        $map = [];
        foreach ($skills as $skill) {
            $name = trim((string) $skill);
            if ($name !== '') {
                $map[mb_strtolower($name)] ??= $name;
            }
        }

        return $map;
    }

    private function norm(?string $value): string
    {
        return trim(mb_strtolower((string) $value));
    }

    /** @return list<string> */
    private function tokens(string $text): array
    {
        $parts = preg_split('/[^\p{L}\p{N}+#]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokens = [];

        foreach ($parts as $part) {
            if (mb_strlen($part) < 2 || in_array($part, self::STOPWORDS, true)) {
                continue;
            }
            if (mb_strlen($part) > 3 && str_ends_with($part, 's') && ! str_ends_with($part, 'ss')) {
                $part = mb_substr($part, 0, -1);
            }
            $tokens[$part] = true;
        }

        return array_map('strval', array_keys($tokens));
    }
}
