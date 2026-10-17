<?php

namespace Tests\Unit;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Admin\LeadController;
use App\Models\Skill;
use App\Support\Db;
use PHPUnit\Framework\TestCase;

class SupportTest extends TestCase
{
    public function test_skill_parse_trims_and_removes_duplicates_ignoring_case(): void
    {
        $this->assertSame(['php', 'Laravel', 'sql'], Skill::parse("php, Laravel ,sql, PHP,\n"));
    }

    public function test_skill_parse_accepts_arrays_and_empty_input(): void
    {
        $this->assertSame(['A', 'B'], Skill::parse(['A', ' B ', 'a']));
        $this->assertSame([], Skill::parse(null));
        $this->assertSame([], Skill::parse(''));
    }

    public function test_search_terms_cannot_inject_like_wildcards(): void
    {
        $this->assertSame('%100 off%', Db::contains('100% off'));
        $this->assertSame('%a b%', Db::contains('a_b'));
    }

    public function test_csv_cells_that_look_like_formulas_are_neutralised(): void
    {
        $this->assertSame("'=1+1", LeadController::safeCell('=1+1'));
        $this->assertSame("'@SUM(A1)", LeadController::safeCell('@SUM(A1)'));
        $this->assertSame('Ama', LeadController::safeCell('Ama'));
        $this->assertSame(5, LeadController::safeCell(5));
    }

    public function test_final_statuses(): void
    {
        $this->assertTrue(ApplicationStatus::Hired->isFinal());
        $this->assertTrue(ApplicationStatus::Withdrawn->isFinal());
        $this->assertFalse(ApplicationStatus::Interview->isFinal());
        $this->assertCount(6, ApplicationStatus::pipeline());
    }
}
