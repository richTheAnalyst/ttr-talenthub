<?php

namespace Tests\Feature;

use App\Models\HiringRequest;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadCaptureTest extends TestCase
{
    use RefreshDatabase;

    private function studyPayload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Adwoa Frimpong', 'email' => 'adwoa@example.com', 'phone' => '0277000111', 'destination' => 'Germany',
            'level' => 'masters', 'intake' => 'september', 'field_of_study' => 'Engineering', 'consent' => '1',
        ];
    }

    public function test_a_study_abroad_enquiry_becomes_a_scored_lead(): void
    {
        $this->post('/study-abroad', $this->studyPayload())->assertRedirect(route('study.create'))->assertSessionHas('status');

        $lead = Lead::firstOrFail();
        $this->assertSame(Lead::TYPE_STUDY_ABROAD, $lead->type);
        $this->assertSame('Germany', $lead->details['destination']);
        $this->assertGreaterThan(50, $lead->score);
        $this->assertSame('new', $lead->stage->value);
    }

    public function test_the_honeypot_silently_drops_bot_submissions(): void
    {
        $this->post('/study-abroad', $this->studyPayload(['website' => 'http://spam.example']))->assertSessionHas('status');

        $this->assertSame(0, Lead::count());
    }

    public function test_study_form_validation(): void
    {
        $this->post('/study-abroad', ['name' => '', 'email' => 'nope', 'destination' => 'Atlantis', 'level' => 'wizard'])
            ->assertSessionHasErrors(['name', 'email', 'phone', 'destination', 'level', 'consent']);
    }

    public function test_the_same_person_enquiring_twice_updates_one_lead(): void
    {
        $this->post('/study-abroad', $this->studyPayload());
        $this->post('/study-abroad', $this->studyPayload(['destination' => 'Canada']));

        $this->assertSame(1, Lead::count());
        $this->assertSame('Canada', Lead::first()->details['destination']);
    }

    public function test_a_hiring_request_creates_a_request_and_an_employer_lead(): void
    {
        $this->post('/hire-talent', [
            'company_name' => 'Volta Retail', 'contact_name' => 'Selorm', 'contact_email' => 'selorm@volta.test', 'contact_phone' => '0244111222',
            'role_title' => 'Store Manager', 'headcount' => 6, 'urgency' => 'high',
        ])->assertRedirect(route('hire.create'));

        $this->assertDatabaseHas('hiring_requests', ['company_name' => 'Volta Retail', 'headcount' => 6, 'status' => 'new']);
        $lead = Lead::where('type', Lead::TYPE_EMPLOYER)->firstOrFail();
        $this->assertGreaterThanOrEqual(70, $lead->score);
        $this->assertSame(1, HiringRequest::count());
    }

    public function test_public_forms_are_rate_limited(): void
    {
        $statuses = [];
        for ($i = 0; $i < 8; $i++) {
            $statuses[] = $this->post('/study-abroad', $this->studyPayload(['email' => "p{$i}@example.com"]))->getStatusCode();
        }

        $this->assertContains(429, $statuses);
    }

    public function test_the_crm_webhook_is_called_with_a_signature_when_configured(): void
    {
        config(['talenthub.crm.webhook_url' => 'https://crm.example/hook', 'talenthub.crm.secret' => 's3cret']);
        \Illuminate\Support\Facades\Http::fake(['crm.example/*' => \Illuminate\Support\Facades\Http::response(['reference' => 'CRM-9'], 200)]);

        $this->post('/study-abroad', $this->studyPayload());

        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            return $request->url() === 'https://crm.example/hook'
                && $request->header('X-TalentHub-Signature')[0] === hash_hmac('sha256', $request->body(), 's3cret');
        });
        $this->assertSame('CRM-9', Lead::first()->crm_reference);
    }
}
