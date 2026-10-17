<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_a_token_that_authenticates(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'role' => 'candidate', 'name' => 'Kofi', 'email' => 'kofi@example.com',
            'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD,
        ])->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'email', 'role']]);

        $this->withToken($response->json('token'))->getJson('/api/v1/me')
            ->assertOk()->assertJsonPath('user.email', 'kofi@example.com')->assertJsonStructure(['profile' => ['completeness']]);
    }

    public function test_protected_endpoints_need_a_token(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->getJson('/api/v1/applications')->assertUnauthorized();
    }

    public function test_login_failure_is_a_validation_error_and_inactive_users_are_blocked(): void
    {
        $user = User::factory()->create();
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'nope'])->assertUnprocessable();

        $user->update(['is_active' => false]);
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => self::PASSWORD])->assertUnprocessable();
    }

    public function test_jobs_are_public_and_searchable(): void
    {
        $employer = $this->makeEmployer()->employer;
        $this->makeJob($employer, ['title' => 'Credit Analyst']);
        $this->makeJob($employer, ['title' => 'Draft only', 'status' => 'draft']);

        $this->getJson('/api/v1/jobs?q=credit')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Credit Analyst');
    }

    public function test_a_candidate_can_apply_through_the_api_and_list_applications(): void
    {
        Notification::fake();

        $job = $this->makeJob($this->makeEmployer()->employer, [], ['PHP']);
        $candidate = $this->makeCandidate([], ['PHP']);
        $cv = $this->makeCv($candidate);
        $token = $candidate->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson("/api/v1/jobs/{$job->id}/apply", ['cv_id' => $cv->id])->assertCreated()->assertJsonPath('data.status.value', 'submitted');
        $this->withToken($token)->postJson("/api/v1/jobs/{$job->id}/apply", ['cv_id' => $cv->id])->assertUnprocessable();
        $this->withToken($token)->getJson('/api/v1/applications')->assertOk();
    }

    public function test_employers_cannot_use_candidate_endpoints(): void
    {
        $employer = $this->makeEmployer();
        $token = $employer->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/applications')->assertForbidden();
    }

    public function test_the_lead_endpoint_accepts_partner_submissions(): void
    {
        $this->postJson('/api/v1/leads', ['type' => 'study_abroad', 'name' => 'Ama', 'email' => 'ama@example.com', 'details' => ['destination' => 'Canada']])
            ->assertCreated()->assertJsonStructure(['reference']);

        $this->postJson('/api/v1/leads', ['type' => 'bogus', 'name' => 'x'])->assertUnprocessable();
    }
}
