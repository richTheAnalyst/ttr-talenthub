<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\CandidateProfile;
use App\Models\Employer;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegistrationService
{
    public function __construct(
        private ProfileService $profiles,
        private LeadService $leads,
    ) {
    }

    /**
     * Create an account with its role-specific record, and open a CRM lead.
     *
     * @param  array{name:string,email:string,password:string,role:string,phone?:?string,interest_type?:?string,company_name?:?string}  $data
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $role = Role::from($data['role']);

            $user = User::create([
                'name' => $data['name'],
                'email' => mb_strtolower($data['email']),
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
                'role' => $role,
            ]);

            if ($role === Role::Employer) {
                Employer::create([
                    'user_id' => $user->id,
                    'company_name' => $data['company_name'] ?? $data['name'],
                ]);

                $this->leads->capture(Lead::TYPE_EMPLOYER, [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'country' => 'Ghana',
                    'source' => 'registration',
                    'details' => ['company' => $data['company_name'] ?? null],
                ], [], $user);

                return $user;
            }

            $profile = CandidateProfile::create([
                'user_id' => $user->id,
                'interest_type' => $data['interest_type'] ?? 'job',
            ]);
            $this->profiles->refreshCompleteness($user, $profile);
            $this->profiles->syncLeads($user, $profile->refresh());

            return $user;
        });
    }
}
