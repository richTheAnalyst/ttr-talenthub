<?php

namespace Tests\Feature;

use App\Jobs\ProcessNewJobPosting;
use App\Models\JobPosting;
use App\Notifications\JobAlertMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmployerJobsTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'title' => 'Customer Service Officer',
            'description' => 'Handle customer enquiries in branch and by phone with care and speed.',
            'location' => 'Accra', 'job_type' => 'full_time', 'required_skills' => 'Customer service, Communication',
            'optional_skills' => 'Data entry', 'action' => 'publish',
        ];
    }

    public function test_publishing_a_job_opens_it_attaches_skills_and_queues_matching(): void
    {
        Queue::fake();
        $employer = $this->makeEmployer();

        $this->actingAs($employer)->post(route('employer.jobs.store'), $this->payload())->assertRedirect(route('employer.jobs.index'));

        $job = JobPosting::firstOrFail();
        $this->assertSame('open', $job->status);
        $this->assertSame(3, $job->skills()->count());
        $this->assertSame(2, $job->skills()->wherePivot('is_required', true)->count());
        Queue::assertPushed(ProcessNewJobPosting::class);
    }

    public function test_saving_a_draft_does_not_publish_or_notify(): void
    {
        Queue::fake();
        $employer = $this->makeEmployer();

        $this->actingAs($employer)->post(route('employer.jobs.store'), $this->payload(['action' => 'draft']));

        $this->assertSame('draft', JobPosting::firstOrFail()->status);
        Queue::assertNothingPushed();
        $this->get(route('jobs.index'))->assertDontSee('Customer Service Officer');
    }

    public function test_job_validation(): void
    {
        $employer = $this->makeEmployer();

        $this->actingAs($employer)->post(route('employer.jobs.store'), $this->payload(['title' => '', 'description' => 'short', 'salary_min' => 500, 'salary_max' => 100]))
            ->assertSessionHasErrors(['title', 'description', 'salary_max']);
    }

    public function test_matching_candidates_and_alert_owners_are_notified_when_a_job_goes_live(): void
    {
        Notification::fake();
        $employer = $this->makeEmployer();

        $strong = $this->makeCandidate(['desired_title' => 'Customer Service Officer', 'location' => 'Accra', 'years_experience' => 3, 'desired_job_type' => 'full_time', 'is_searchable' => true, 'interest_type' => 'job'], ['Customer service', 'Communication', 'Data entry']);
        $weak = $this->makeCandidate(['desired_title' => 'Welder', 'location' => 'Tamale', 'is_searchable' => true, 'interest_type' => 'job'], ['Welding']);

        $this->actingAs($employer)->post(route('employer.jobs.store'), $this->payload());

        Notification::assertSentTo($strong, JobAlertMatch::class);
        Notification::assertNotSentTo($weak, JobAlertMatch::class);
    }

    public function test_employers_only_manage_their_own_jobs(): void
    {
        $owner = $this->makeEmployer();
        $stranger = $this->makeEmployer(['company_name' => 'Other Ltd']);
        $job = $this->makeJob($owner->employer);

        $this->actingAs($stranger)->get(route('employer.jobs.edit', $job))->assertForbidden();
        $this->actingAs($stranger)->put(route('employer.jobs.update', $job), $this->payload())->assertForbidden();
        $this->actingAs($stranger)->post(route('employer.jobs.close', $job))->assertForbidden();
        $this->actingAs($stranger)->delete(route('employer.jobs.destroy', $job))->assertForbidden();

        $this->actingAs($owner)->post(route('employer.jobs.close', $job));
        $this->assertSame('closed', $job->fresh()->status);
    }

    public function test_employer_screens_render(): void
    {
        $employer = $this->makeEmployer();
        $job = $this->makeJob($employer->employer, [], ['PHP']);

        foreach ([route('employer.dashboard'), route('employer.jobs.index'), route('employer.jobs.create'), route('employer.jobs.edit', $job),
            route('employer.jobs.applicants', $job), route('employer.company.edit'), route('employer.hiring.index'), route('employer.hiring.create')] as $url) {
            $this->actingAs($employer)->get($url)->assertOk();
        }
    }

    public function test_public_search_filters_by_keyword_and_type(): void
    {
        $employer = $this->makeEmployer()->employer;
        $this->makeJob($employer, ['title' => 'Warehouse Supervisor', 'job_type' => 'full_time']);
        $this->makeJob($employer, ['title' => 'IT Support Intern', 'job_type' => 'internship']);

        $this->get('/jobs?q=warehouse')->assertSee('Warehouse Supervisor')->assertDontSee('IT Support Intern');
        $this->get('/jobs?type=internship')->assertSee('IT Support Intern')->assertDontSee('Warehouse Supervisor');
    }

    public function test_draft_and_expired_jobs_are_hidden_from_the_public(): void
    {
        $employer = $this->makeEmployer()->employer;
        $draft = $this->makeJob($employer, ['status' => 'draft']);
        $expired = $this->makeJob($employer, ['expires_at' => now()->subDay()]);

        $this->get(route('jobs.show', $draft))->assertNotFound();
        $this->get(route('jobs.show', $expired))->assertNotFound();
    }
}
