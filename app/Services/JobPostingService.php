<?php

namespace App\Services;

use App\Jobs\ProcessNewJobPosting;
use App\Models\Employer;
use App\Models\JobPosting;
use App\Models\Skill;
use Illuminate\Support\Arr;
use App\Notifications\JobApprovalNotice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Enums\Role;
use App\Models\User;

class JobPostingService
{
    /**
     * Create or update a job, sync its skills and, when it goes live for the
     * first time, queue the matching and alert fan-out.
     *
     * @param  array<string,mixed>  $data  validated input; "action" is "publish" or "draft"
     */
    public function save(Employer $employer, ?JobPosting $job, array $data, bool $autoApprove = false): JobPosting
    {
        $needsReview = false;

        $saved = DB::transaction(function () use ($employer, $job, $data, $autoApprove, &$needsReview) {
            $publish = ($data['action'] ?? 'draft') === 'publish';

            $attributes = Arr::only($data, [
                'title', 'description', 'location', 'country', 'job_type', 'salary_min', 'salary_max',
                'currency', 'experience_min', 'education_level', 'expires_at', 'engagement', 'client_budget',
            ]);
            $attributes['engagement'] ??= 'recruitment';
            if ($attributes['engagement'] !== 'outsourcing') {
                $attributes['client_budget'] = null;
            }
            $attributes['experience_min'] = (int) ($attributes['experience_min'] ?? 0);

            if ($job === null) {
                $job = new JobPosting($attributes);
                $job->employer_id = $employer->id;
                $job->status = JobPosting::STATUS_DRAFT;
            } else {
                $job->fill($attributes);
            }

            $wasOpen = $job->status === JobPosting::STATUS_OPEN;

            if ($publish && ! $wasOpen) {
                $job->status = JobPosting::STATUS_OPEN;
                $job->published_at = now();
            }

            // Every new or edited live job waits for an admin, unless an admin made the change.
            if ($publish || $wasOpen) {
                if ($autoApprove) {
                    $job->approval_status = JobPosting::APPROVAL_APPROVED;
                    $job->approval_note = null;
                    $job->approved_at ??= now();
                } else {
                    $job->approval_status = JobPosting::APPROVAL_PENDING;
                    $job->approval_note = null;
                    $needsReview = true;
                }
            }

            $job->save();

            $sync = [];
            foreach (Skill::idsFor(Skill::parse($data['required_skills'] ?? null)) as $id) {
                $sync[$id] = ['is_required' => true];
            }
            foreach (Skill::idsFor(Skill::parse($data['optional_skills'] ?? null)) as $id) {
                $sync[$id] ??= ['is_required' => false];
            }
            $job->skills()->sync($sync);

            // Matching and alerts run when an admin approves the job (or right away if an admin posted it).
            if ($autoApprove && $publish && ! $wasOpen) {
                ProcessNewJobPosting::dispatch($job->id)->afterCommit();
            }

            return $job->load('skills');
        });

        if ($needsReview) {
            Notification::send(
                User::where('role', Role::Admin->value)->where('is_active', true)->get(),
                new JobApprovalNotice($saved, 'submitted')
            );
        }

        return $saved;
    }

    /** Validation rules shared by the employer form and any future API. */
    public static function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'min:30', 'max:10000'],
            'location' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:80'],
            'job_type' => ['required', 'in:'.implode(',', array_keys(\App\Enums\JobType::options()))],
            'salary_min' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'max:10000000', 'gte:salary_min'],
            'currency' => ['nullable', 'string', 'size:3'],
            'experience_min' => ['nullable', 'integer', 'min:0', 'max:40'],
            'education_level' => ['nullable', 'in:'.implode(',', array_keys(config('talenthub.education_levels')))],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'required_skills' => ['nullable', 'string', 'max:600'],
            'optional_skills' => ['nullable', 'string', 'max:600'],
            'engagement' => ['required', 'in:outsourcing,recruitment'],
            'client_budget' => ['nullable', 'required_if:engagement,outsourcing', 'integer', 'min:1', 'max:10000000'],
            'action' => ['required', 'in:draft,publish'],
        ];
    }
}