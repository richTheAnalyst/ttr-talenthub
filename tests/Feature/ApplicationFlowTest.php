<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Notifications\ApplicationStatusChanged;
use App\Notifications\NewApplicationReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApplicationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_candidate_can_apply_and_the_employer_is_told(): void
    {
        Notification::fake();

        $employer = $this->makeEmployer();
        $job = $this->makeJob($employer->employer, [], ['PHP', 'Laravel']);
        $candidate = $this->makeCandidate(['desired_title' => 'Laravel Developer', 'location' => 'Accra'], ['PHP', 'Laravel']);
        $cv = $this->makeCv($candidate);

        $this->actingAs($candidate)
            ->post(route('candidate.jobs.apply', $job), ['cv_id' => $cv->id, 'cover_note' => 'I can start Monday.'])
            ->assertRedirect();

        $application = Application::firstOrFail();
        $this->assertSame(ApplicationStatus::Submitted, $application->status);
        $this->assertGreaterThan(50, $application->match_score);
        $this->assertSame(1, $application->histories()->count());

        Notification::assertSentTo($employer, NewApplicationReceived::class);
    }

    public function test_applying_twice_is_refused(): void
    {
        Notification::fake();

        $employer = $this->makeEmployer();
        $job = $this->makeJob($employer->employer);
        $candidate = $this->makeCandidate();
        $this->makeCv($candidate);

        $this->actingAs($candidate)->post(route('candidate.jobs.apply', $job));
        $this->actingAs($candidate)->post(route('candidate.jobs.apply', $job))->assertSessionHasErrors('job');

        $this->assertSame(1, Application::count());
    }

    public function test_applying_without_a_cv_is_refused(): void
    {
        $job = $this->makeJob($this->makeEmployer()->employer);
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)->post(route('candidate.jobs.apply', $job))->assertSessionHasErrors('cv_id');

        $this->assertSame(0, Application::count());
    }

    public function test_closed_and_expired_jobs_do_not_accept_applications(): void
    {
        $employer = $this->makeEmployer()->employer;
        $candidate = $this->makeCandidate();
        $this->makeCv($candidate);

        $closed = $this->makeJob($employer, ['status' => 'closed']);
        $expired = $this->makeJob($employer, ['expires_at' => now()->subDay()]);

        $this->actingAs($candidate)->post(route('candidate.jobs.apply', $closed))->assertSessionHasErrors('job');
        $this->actingAs($candidate)->post(route('candidate.jobs.apply', $expired))->assertSessionHasErrors('job');
    }

    public function test_a_candidate_cannot_apply_with_someone_elses_cv(): void
    {
        $job = $this->makeJob($this->makeEmployer()->employer);
        $mine = $this->makeCandidate();
        $other = $this->makeCandidate();
        $this->makeCv($mine);
        $theirs = $this->makeCv($other);

        $this->actingAs($mine)->post(route('candidate.jobs.apply', $job), ['cv_id' => $theirs->id])->assertSessionHasErrors('cv_id');
    }

    public function test_the_employer_can_move_an_application_and_the_candidate_is_notified(): void
    {
        Notification::fake();

        $employer = $this->makeEmployer();
        $job = $this->makeJob($employer->employer);
        $candidate = $this->makeCandidate();
        $this->makeCv($candidate);
        $this->actingAs($candidate)->post(route('candidate.jobs.apply', $job));
        $application = Application::firstOrFail();

        $this->actingAs($employer)
            ->patch(route('employer.applications.status', $application), ['status' => 'interview', 'note' => 'Friday 10am'])
            ->assertSessionHasNoErrors();

        $this->assertSame(ApplicationStatus::Interview, $application->fresh()->status);
        $this->assertDatabaseHas('application_status_histories', ['application_id' => $application->id, 'to_status' => 'interview', 'note' => 'Friday 10am']);
        Notification::assertSentTo($candidate, ApplicationStatusChanged::class);
    }

    public function test_another_employer_cannot_touch_the_application(): void
    {
        Notification::fake();

        $owner = $this->makeEmployer();
        $stranger = $this->makeEmployer(['company_name' => 'Other Ltd']);
        $job = $this->makeJob($owner->employer);
        $candidate = $this->makeCandidate();
        $this->makeCv($candidate);
        $this->actingAs($candidate)->post(route('candidate.jobs.apply', $job));
        $application = Application::firstOrFail();

        $this->actingAs($stranger)->patch(route('employer.applications.status', $application), ['status' => 'hired'])->assertForbidden();
        $this->actingAs($stranger)->get(route('employer.jobs.applicants', $job))->assertForbidden();
        $this->assertSame(ApplicationStatus::Submitted, $application->fresh()->status);
    }

    public function test_a_candidate_can_withdraw_but_only_their_own_application(): void
    {
        Notification::fake();

        $job = $this->makeJob($this->makeEmployer()->employer);
        $candidate = $this->makeCandidate();
        $intruder = $this->makeCandidate();
        $this->makeCv($candidate);
        $this->actingAs($candidate)->post(route('candidate.jobs.apply', $job));
        $application = Application::firstOrFail();

        $this->actingAs($intruder)->patch(route('candidate.applications.withdraw', $application))->assertForbidden();
        $this->actingAs($intruder)->get(route('candidate.applications.show', $application))->assertForbidden();

        $this->actingAs($candidate)->patch(route('candidate.applications.withdraw', $application))->assertSessionHasNoErrors();
        $this->assertSame(ApplicationStatus::Withdrawn, $application->fresh()->status);
    }

    public function test_the_tracker_and_job_pages_render_for_a_candidate(): void
    {
        Notification::fake();

        $employer = $this->makeEmployer();
        $job = $this->makeJob($employer->employer, [], ['PHP']);
        $candidate = $this->makeCandidate([], ['PHP']);
        $this->makeCv($candidate);
        $this->actingAs($candidate)->post(route('candidate.jobs.apply', $job));
        $application = Application::firstOrFail();

        $this->actingAs($candidate)->get(route('candidate.applications.show', $application))->assertOk()->assertSee('Junior Laravel Developer');
        $this->actingAs($candidate)->get(route('candidate.applications.index'))->assertOk();
        $this->actingAs($candidate)->get(route('jobs.show', $job))->assertOk()->assertSee('Track application');
        $this->actingAs($candidate)->get(route('candidate.dashboard'))->assertOk();
        $this->actingAs($candidate)->get(route('candidate.matches'))->assertOk();
    }
}
