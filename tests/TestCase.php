<?php

namespace Tests;

use App\Enums\Role;
use App\Models\CandidateProfile;
use App\Models\Cv;
use App\Models\Employer;
use App\Models\JobPosting;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    public const PASSWORD = 'Password123';

    protected function makeCandidate(array $profile = [], array $skills = [], array $user = []): User
    {
        $candidate = User::factory()->create($user);

        $profile = CandidateProfile::create(['user_id' => $candidate->id] + $profile);
        if ($skills) {
            $profile->skills()->sync(Skill::idsFor($skills));
        }

        return $candidate;
    }

    protected function makeEmployer(array $company = [], array $user = []): User
    {
        $employerUser = User::factory()->employer()->create($user);

        Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Acme Ltd'] + $company);

        return $employerUser->load('employer');
    }

    protected function makeAdmin(): User
    {
        return User::factory()->admin()->create();
    }

    protected function makeJob(Employer $employer, array $attributes = [], array $required = [], array $optional = []): JobPosting
    {
        $job = JobPosting::create($attributes + [
            'employer_id' => $employer->id,
            'title' => 'Junior Laravel Developer',
            'description' => 'Build and maintain web applications for our clients in Accra.',
            'location' => 'Accra',
            'country' => 'Ghana',
            'job_type' => 'full_time',
            'status' => JobPosting::STATUS_OPEN,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $sync = [];
        foreach (Skill::idsFor($required) as $id) {
            $sync[$id] = ['is_required' => true];
        }
        foreach (Skill::idsFor($optional) as $id) {
            $sync[$id] ??= ['is_required' => false];
        }
        $job->skills()->sync($sync);

        return $job;
    }

    /** A CV row backed by a real file on the faked private disk. */
    protected function makeCv(User $user, bool $primary = true): Cv
    {
        $disk = Storage::fake(config('talenthub.cv.disk'));
        $path = 'u'.$user->id.'/'.uniqid().'.pdf';
        $disk->put($path, '%PDF-1.4 test');

        return Cv::create([
            'user_id' => $user->id,
            'original_name' => 'My_CV.pdf',
            'path' => $path,
            'mime' => 'application/pdf',
            'size' => 13,
            'is_primary' => $primary,
        ]);
    }

    protected function role(Role $role): Role
    {
        return $role;
    }
}
