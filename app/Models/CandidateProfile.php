<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CandidateProfile extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'consent_marketing' => 'boolean',
            'is_searchable' => 'boolean',
            'years_experience' => 'integer',
            'expected_salary' => 'integer',
            'completeness' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'candidate_skill');
    }

    public function scopeSearchable(Builder $query): Builder
    {
        return $query->where('is_searchable', true);
    }

    public function scopeWantsJobs(Builder $query): Builder
    {
        return $query->whereIn('interest_type', ['job', 'both']);
    }

    public function wantsStudyAbroad(): bool
    {
        return in_array($this->interest_type, ['study_abroad', 'both'], true);
    }

    /** Plain-array view of this profile for the matching engine. */
    public function matchPayload(): array
    {
        return [
            'skills' => $this->skills->pluck('name')->all(),
            'headline' => $this->headline,
            'desired_title' => $this->desired_title,
            'location' => $this->location,
            'desired_location' => $this->desired_location,
            'country' => $this->country,
            'years_experience' => (int) $this->years_experience,
            'education_level' => $this->education_level,
            'desired_job_type' => $this->desired_job_type,
        ];
    }
}
