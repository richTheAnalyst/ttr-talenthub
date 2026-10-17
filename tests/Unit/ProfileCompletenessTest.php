<?php

namespace Tests\Unit;

use App\Services\ProfileCompleteness;
use PHPUnit\Framework\TestCase;

class ProfileCompletenessTest extends TestCase
{
    public function test_an_empty_profile_is_zero_percent(): void
    {
        $this->assertSame(0, ProfileCompleteness::calculate([], 0, false, null));
    }

    public function test_a_full_profile_is_one_hundred_percent(): void
    {
        $profile = [
            'headline' => 'Developer', 'location' => 'Accra', 'education_level' => 'bachelors', 'years_experience' => 3,
            'desired_title' => 'Developer', 'bio' => 'About me', 'interest_type' => 'job', 'desired_job_type' => 'full_time',
        ];

        $this->assertSame(100, ProfileCompleteness::calculate($profile, 5, true, '0244000000'));
    }

    public function test_uploading_a_cv_is_worth_twenty_points(): void
    {
        $this->assertSame(20, ProfileCompleteness::calculate([], 0, true, null));
    }

    public function test_study_abroad_candidates_are_judged_on_destination_not_job_type(): void
    {
        $withDestination = ProfileCompleteness::calculate(['interest_type' => 'study_abroad', 'study_destination' => 'Canada'], 0, false, null);
        $withJobType = ProfileCompleteness::calculate(['interest_type' => 'study_abroad', 'desired_job_type' => 'full_time'], 0, false, null);

        $this->assertSame(5, $withDestination);
        $this->assertSame(0, $withJobType);
    }
}
