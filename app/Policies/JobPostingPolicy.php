<?php

namespace App\Policies;

use App\Models\JobPosting;
use App\Models\User;

class JobPostingPolicy
{
    /** Edit, publish, close or review applicants of a job. */
    public function manage(User $user, JobPosting $job): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isEmployer() && $user->employer?->id === $job->employer_id;
    }
}
