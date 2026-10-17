<?php

namespace Tests\Unit;

use App\Services\LeadScorer;
use PHPUnit\Framework\TestCase;

class LeadScorerTest extends TestCase
{
    public function test_base_score_depends_on_lead_type(): void
    {
        $scorer = new LeadScorer;

        $this->assertSame(30, $scorer->score('job_seeker'));
        $this->assertSame(35, $scorer->score('study_abroad'));
        $this->assertSame(45, $scorer->score('employer'));
    }

    public function test_contact_details_and_study_intent_raise_the_score(): void
    {
        $score = (new LeadScorer)->score('study_abroad', [
            'has_email' => true, 'has_phone' => true, 'destination' => 'Canada', 'study_level' => 'masters',
        ]);

        $this->assertSame(35 + 5 + 10 + 10 + 5, $score);
    }

    public function test_urgent_large_employer_requests_rank_higher_than_relaxed_small_ones(): void
    {
        $scorer = new LeadScorer;

        $urgent = $scorer->score('employer', ['urgency' => 'high', 'headcount' => 10]);
        $relaxed = $scorer->score('employer', ['urgency' => 'low', 'headcount' => 1]);

        $this->assertGreaterThan($relaxed, $urgent);
    }

    public function test_score_is_capped_at_100(): void
    {
        $score = (new LeadScorer)->score('employer', [
            'has_email' => true, 'has_phone' => true, 'registered' => true, 'has_cv' => true, 'consent_marketing' => true,
            'completeness' => 100, 'urgency' => 'high', 'headcount' => 50, 'source' => 'referral',
        ]);

        $this->assertSame(100, $score);
    }
}
