<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\Cv;
use App\Models\User;

class CvPolicy
{
    /**
     * A CV can be read by its owner, by an admin, and by an employer only if
     * the candidate attached it to an application for one of that employer's jobs.
     */
    public function view(User $user, Cv $cv): bool
    {
        if ($cv->user_id === $user->id || $user->isAdmin()) {
            return true;
        }

        if (! $user->isEmployer() || ! $user->employer) {
            return false;
        }

        return Application::where('cv_id', $cv->id)
            ->whereHas('job', fn ($q) => $q->where('employer_id', $user->employer->id))
            ->exists();
    }

    public function update(User $user, Cv $cv): bool
    {
        return $cv->user_id === $user->id;
    }

    public function delete(User $user, Cv $cv): bool
    {
        return $cv->user_id === $user->id;
    }
}
