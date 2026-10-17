<?php

namespace Tests\Feature;

use App\Console\Commands\RemindStaleLeads;
use App\Models\Lead;
use App\Notifications\StaleLeadsReminder;
use App\Services\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_screens_render(): void
    {
        $admin = $this->makeAdmin();
        $lead = app(LeadService::class)->capture('study_abroad', ['name' => 'Ama', 'email' => 'ama@example.com', 'details' => ['destination' => 'UK']]);
        $this->makeEmployer();

        foreach ([route('admin.dashboard'), route('admin.leads.index'), route('admin.leads.show', $lead), route('admin.hiring.index'),
            route('admin.users.index'), route('admin.employers.index')] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_admin_can_work_a_lead(): void
    {
        $admin = $this->makeAdmin();
        $lead = app(LeadService::class)->capture('employer', ['name' => 'Selorm', 'email' => 's@example.com']);

        $this->actingAs($admin)->put(route('admin.leads.update', $lead), ['stage' => 'contacted', 'assigned_to' => $admin->id, 'notes' => 'Called, wants 6 managers.'])->assertSessionHasNoErrors();

        $lead->refresh();
        $this->assertSame('contacted', $lead->stage->value);
        $this->assertNotNull($lead->last_contacted_at);
        $this->assertSame($admin->id, $lead->assigned_to);
    }

    public function test_leads_cannot_be_assigned_to_non_admins(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate();
        $lead = app(LeadService::class)->capture('employer', ['name' => 'S', 'email' => 's@example.com']);

        $this->actingAs($admin)->put(route('admin.leads.update', $lead), ['stage' => 'new', 'assigned_to' => $candidate->id])->assertSessionHasErrors('assigned_to');
    }

    public function test_csv_export_neutralises_formulas(): void
    {
        $admin = $this->makeAdmin();
        app(LeadService::class)->capture('study_abroad', ['name' => '=HYPERLINK("http://evil")', 'email' => 'e@example.com']);

        $body = $this->actingAs($admin)->get(route('admin.leads.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $body);
        $this->assertStringNotContainsString(',=HYPERLINK', $body);
    }

    public function test_deactivating_a_user_revokes_their_tokens_and_logs_them_out_of_the_api(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate();
        $token = $candidate->createToken('phone')->plainTextToken;

        $this->actingAs($admin)->patch(route('admin.users.toggle', $candidate))->assertSessionHasNoErrors();

        $this->assertFalse($candidate->fresh()->is_active);
        $this->assertSame(0, $candidate->tokens()->count());
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_admins_cannot_deactivate_themselves(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->patch(route('admin.users.toggle', $admin))->assertSessionHasErrors('user');
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_verifying_an_employer_shows_the_badge_on_their_jobs(): void
    {
        $admin = $this->makeAdmin();
        $employer = $this->makeEmployer();
        $job = $this->makeJob($employer->employer);

        $this->actingAs($admin)->patch(route('admin.employers.verify', $employer->employer));

        $this->assertTrue($employer->employer->fresh()->isVerified());
        $this->get(route('jobs.show', $job))->assertSee('Verified employer');
    }

    public function test_stale_leads_trigger_a_reminder_to_admins(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $lead = app(LeadService::class)->capture('study_abroad', ['name' => 'Old', 'email' => 'old@example.com']);
        Lead::whereKey($lead->id)->update(['created_at' => now()->subHours(72)]);

        $this->artisan('talenthub:remind-stale-leads')->assertSuccessful();

        Notification::assertSentTo($admin, StaleLeadsReminder::class);
    }
}
