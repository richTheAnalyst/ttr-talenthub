<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render(): void
    {
        foreach (['/', '/jobs', '/study-abroad', '/hire-talent', '/login', '/register', '/forgot-password', '/offline'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_a_candidate_can_register_and_gets_a_profile_and_leads(): void
    {
        $this->post('/register', [
            'role' => 'candidate', 'name' => 'Kofi Asante', 'email' => 'Kofi@Example.com', 'phone' => '0244000111',
            'interest_type' => 'both', 'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD, 'terms' => '1',
        ])->assertRedirect(route('candidate.dashboard'));

        $user = User::where('email', 'kofi@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('candidate', $user->role->value);
        $this->assertNotNull($user->candidateProfile);
        $this->assertDatabaseHas('leads', ['email' => 'kofi@example.com', 'type' => Lead::TYPE_JOB_SEEKER]);
        $this->assertDatabaseHas('leads', ['email' => 'kofi@example.com', 'type' => Lead::TYPE_STUDY_ABROAD]);
    }

    public function test_an_employer_can_register_with_a_company(): void
    {
        $this->post('/register', [
            'role' => 'employer', 'name' => 'Ama Boateng', 'company_name' => 'Golden Coast Logistics', 'email' => 'ama@gcl.test',
            'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD, 'terms' => '1',
        ])->assertRedirect(route('employer.dashboard'));

        $this->assertDatabaseHas('employers', ['company_name' => 'Golden Coast Logistics']);
        $this->assertDatabaseHas('leads', ['email' => 'ama@gcl.test', 'type' => Lead::TYPE_EMPLOYER]);
    }

    public function test_registration_rejects_weak_passwords_duplicate_emails_and_missing_terms(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post('/register', ['role' => 'candidate', 'name' => 'A', 'email' => 'new@example.com', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors(['password', 'terms']);

        $this->post('/register', ['role' => 'candidate', 'name' => 'A', 'email' => 'taken@example.com', 'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD, 'terms' => '1'])
            ->assertSessionHasErrors('email');

        $this->post('/register', ['role' => 'admin', 'name' => 'Sneaky', 'email' => 'x@example.com', 'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD, 'terms' => '1'])
            ->assertSessionHasErrors('role');
    }

    public function test_login_sends_each_role_to_its_own_dashboard(): void
    {
        $candidate = $this->makeCandidate();
        $this->post('/login', ['email' => $candidate->email, 'password' => self::PASSWORD])->assertRedirect(route('dashboard'));
        $this->get('/dashboard')->assertRedirect(route('candidate.dashboard'));
        $this->post('/logout');

        $admin = $this->makeAdmin();
        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));
    }

    public function test_wrong_password_and_deactivated_accounts_cannot_sign_in(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $inactive = User::factory()->inactive()->create();
        $this->post('/login', ['email' => $inactive->email, 'password' => self::PASSWORD])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_roles_cannot_enter_each_others_areas(): void
    {
        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer();

        $this->actingAs($candidate)->get('/employer')->assertForbidden();
        $this->actingAs($candidate)->get('/admin')->assertForbidden();
        $this->actingAs($employer)->get('/candidate')->assertForbidden();
        $this->actingAs($employer)->get('/admin/leads')->assertForbidden();
    }

    public function test_guests_are_sent_to_sign_in(): void
    {
        foreach (['/candidate', '/employer', '/admin', '/notifications'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_a_deactivated_user_with_an_open_session_is_signed_out(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)->get('/candidate')->assertOk();

        $candidate->update(['is_active' => false]);

        $this->actingAs($candidate->fresh())->get('/candidate')->assertRedirect(route('login'));
    }

    public function test_employer_registration_creates_exactly_one_employer_row(): void
    {
        $this->post('/register', [
            'role' => 'employer', 'name' => 'B', 'company_name' => 'Beta Ltd', 'email' => 'b@beta.test',
            'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD, 'terms' => '1',
        ]);

        $this->assertSame(1, Employer::count());
    }
}
