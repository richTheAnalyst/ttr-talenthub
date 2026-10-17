<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    /** The candidate, the employer who owns the job, or an admin. */
    public function view(User $user, Application $application): bool
    {
        return $application->user_id === $user->id || $this->employerOwns($user, $application) || $user->isAdmin();
    }

    public function withdraw(User $user, Application $application): bool
    {
        return $application->user_id === $user->id;
    }

    public function updateStatus(User $user, Application $application): bool
    {
        return $this->employerOwns($user, $application) || $user->isAdmin();
    }

    private function employerOwns(User $user, Application $application): bool
    {
        if (! $user->isEmployer()) {
            return false;
        }

        $application->loadMissing('job');

        return $user->employer?->id === $application->job->employer_id;
    }
}
