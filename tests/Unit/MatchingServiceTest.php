<?php

namespace Tests\Unit;

use App\Services\MatchingService;
use PHPUnit\Framework\TestCase;

class MatchingServiceTest extends TestCase
{
    private function candidate(array $overrides = []): array
    {
        return $overrides + [
            'skills' => ['PHP', 'Laravel', 'MySQL'],
            'headline' => 'Junior web developer',
            'desired_title' => 'Junior Laravel Developer',
            'location' => 'Accra',
            'desired_location' => 'Accra',
            'country' => 'Ghana',
            'years_experience' => 2,
            'education_level' => 'bachelors',
            'desired_job_type' => 'full_time',
        ];
    }

    private function job(array $overrides = []): array
    {
        return $overrides + [
            'title' => 'Junior Laravel Developer',
            'location' => 'Accra',
            'country' => 'Ghana',
            'experience_min' => 1,
            'education_level' => 'bachelors',
            'job_type' => 'full_time',
            'skills_required' => ['php', 'laravel', 'mysql'],
            'skills_optional' => [],
        ];
    }

    public function test_a_perfect_fit_scores_100(): void
    {
        $result = (new MatchingService)->compute($this->candidate(), $this->job());

        $this->assertSame(100, $result['score']);
        $this->assertSame([], $result['missing_skills']);
    }

    public function test_a_poor_fit_scores_low(): void
    {
        $result = (new MatchingService)->compute(
            $this->candidate([
                'skills' => ['Cooking'], 'desired_title' => 'Chef', 'headline' => null, 'location' => 'Accra',
                'desired_location' => 'Accra', 'years_experience' => 0, 'education_level' => 'secondary', 'desired_job_type' => 'internship',
            ]),
            $this->job(['location' => 'Kumasi', 'experience_min' => 3])
        );

        $this->assertLessThan(20, $result['score']);
    }

    public function test_it_reports_matched_and_missing_required_skills_in_job_order(): void
    {
        $result = (new MatchingService)->compute(
            $this->candidate(['skills' => ['PHP', 'laravel']]),
            $this->job(['skills_required' => ['PHP', 'Laravel', 'MySQL', 'Git']])
        );

        $this->assertSame(['PHP', 'Laravel'], $result['matched_skills']);
        $this->assertSame(['MySQL', 'Git'], $result['missing_skills']);
        $this->assertSame(0.5, $result['breakdown']['skills']['ratio']);
    }

    public function test_skill_matching_ignores_case_and_spacing(): void
    {
        $result = (new MatchingService)->compute(
            $this->candidate(['skills' => ['  tailwind css ']]),
            $this->job(['skills_required' => ['Tailwind CSS']])
        );

        $this->assertSame(1.0, $result['breakdown']['skills']['ratio']);
    }

    public function test_remote_jobs_ignore_location(): void
    {
        $result = (new MatchingService)->compute($this->candidate(['location' => 'Tamale', 'desired_location' => 'Tamale']), $this->job(['location' => 'Remote']));

        $this->assertSame(1.0, $result['breakdown']['location']['ratio']);
    }

    public function test_weights_are_normalised_so_they_need_not_add_to_100(): void
    {
        $result = (new MatchingService(['skills' => 1, 'title' => 1]))->compute($this->candidate(), $this->job());

        $this->assertSame(100, $result['score']);
    }

    public function test_a_job_without_skills_is_neutral_on_skills(): void
    {
        $result = (new MatchingService)->compute($this->candidate(), $this->job(['skills_required' => [], 'skills_optional' => []]));

        $this->assertSame(0.5, $result['breakdown']['skills']['ratio']);
    }
}
