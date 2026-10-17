<?php

namespace App\Services;

use App\Jobs\ProcessNewJobPosting;
use App\Models\Employer;
use App\Models\JobPosting;
use App\Models\Skill;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class JobPostingService
{
    /**
     * Create or update a job, sync its skills and, when it goes live for the
     * first time, queue the matching and alert fan-out.
     *
     * @param  array<string,mixed>  $data  validated input; "action" is "publish" or "draft"
     */
    public function save(Employer $employer, ?JobPosting $job, array $data): JobPosting
    {
        return DB::transaction(function () use ($employer, $job, $data) {
            $publish = ($data['action'] ?? 'draft') === 'publish';

            $attributes = Arr::only($data, [
                'title', 'description', 'location', 'country', 'job_type', 'salary_min', 'salary_max',
                'currency', 'experience_min', 'education_level', 'expires_at',
            ]);
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

            $job->save();

            $sync = [];
            foreach (Skill::idsFor(Skill::parse($data['required_skills'] ?? null)) as $id) {
                $sync[$id] = ['is_required' => true];
            }
            foreach (Skill::idsFor(Skill::parse($data['optional_skills'] ?? null)) as $id) {
                $sync[$id] ??= ['is_required' => false];
            }
            $job->skills()->sync($sync);

            if ($publish && ! $wasOpen) {
                ProcessNewJobPosting::dispatch($job->id)->afterCommit();
            }

            return $job->load('skills');
        });
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
            'action' => ['required', 'in:draft,publish'],
        ];
    }
}
