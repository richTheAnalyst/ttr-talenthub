<?php

namespace App\Http\Resources;

use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Cv;
use App\Models\JobAlert;
use App\Models\JobPosting;
use App\Models\User;

/**
 * Turns models into the JSON shapes the API returns. One place to keep the
 * public contract stable while the database evolves.
 */
class Presenter
{
    public static function user(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role->value,
        ];
    }

    /** Requires employer and skills to be loaded. */
    public static function job(JobPosting $job, bool $detailed = false, ?int $score = null): array
    {
        $data = [
            'id' => $job->id,
            'title' => $job->title,
            'company' => $job->employer->company_name,
            'company_verified' => $job->employer->isVerified(),
            'location' => $job->location,
            'country' => $job->country,
            'job_type' => ['value' => $job->job_type->value, 'label' => $job->job_type->label()],
            'salary' => $job->salaryRange(),
            'experience_min' => $job->experience_min,
            'education_level' => $job->education_level,
            'skills' => [
                'required' => $job->skills->filter(fn ($s) => $s->pivot->is_required)->pluck('name')->values(),
                'optional' => $job->skills->reject(fn ($s) => $s->pivot->is_required)->pluck('name')->values(),
            ],
            'published_at' => $job->published_at?->toIso8601String(),
            'expires_at' => $job->expires_at?->toIso8601String(),
        ];

        if ($score !== null) {
            $data['match_score'] = $score;
        }

        if ($detailed) {
            $data['description'] = $job->description;
        }

        return $data;
    }

    /** Requires job.employer to be loaded. */
    public static function application(Application $application): array
    {
        return [
            'id' => $application->id,
            'status' => ['value' => $application->status->value, 'label' => $application->status->label()],
            'match_score' => $application->match_score,
            'cover_note' => $application->cover_note,
            'cv_id' => $application->cv_id,
            'status_changed_at' => $application->status_changed_at?->toIso8601String(),
            'applied_at' => $application->created_at?->toIso8601String(),
            'job' => [
                'id' => $application->job->id,
                'title' => $application->job->title,
                'company' => $application->job->employer->company_name,
            ],
        ];
    }

    /** Requires skills to be loaded. */
    public static function profile(CandidateProfile $profile): array
    {
        return [
            'headline' => $profile->headline,
            'location' => $profile->location,
            'country' => $profile->country,
            'years_experience' => $profile->years_experience,
            'education_level' => $profile->education_level,
            'field_of_study' => $profile->field_of_study,
            'desired_title' => $profile->desired_title,
            'desired_job_type' => $profile->desired_job_type,
            'desired_location' => $profile->desired_location,
            'expected_salary' => $profile->expected_salary,
            'availability' => $profile->availability,
            'bio' => $profile->bio,
            'skills' => $profile->skills->pluck('name')->values(),
            'interest_type' => $profile->interest_type,
            'study_destination' => $profile->study_destination,
            'study_level' => $profile->study_level,
            'study_intake' => $profile->study_intake,
            'consent_marketing' => $profile->consent_marketing,
            'is_searchable' => $profile->is_searchable,
            'completeness' => $profile->completeness,
        ];
    }

    public static function cv(Cv $cv): array
    {
        return [
            'id' => $cv->id,
            'name' => $cv->original_name,
            'size' => $cv->size,
            'is_primary' => $cv->is_primary,
            'uploaded_at' => $cv->created_at?->toIso8601String(),
            'download_url' => route('api.cvs.download', $cv),
        ];
    }

    public static function alert(JobAlert $alert): array
    {
        return [
            'id' => $alert->id,
            'keywords' => $alert->keywords,
            'location' => $alert->location,
            'job_type' => $alert->job_type,
            'min_match' => $alert->min_match,
            'frequency' => $alert->frequency,
            'is_active' => $alert->is_active,
        ];
    }
}
