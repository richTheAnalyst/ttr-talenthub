<?php

namespace App\Services;

use App\Models\CandidateProfile;
use App\Models\Lead;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfileService
{
    /** Columns of candidate_profiles that a candidate may edit. */
    public const FIELDS = [
        'headline', 'location', 'country', 'years_experience', 'education_level', 'field_of_study',
        'desired_title', 'desired_job_type', 'desired_location', 'expected_salary', 'availability', 'bio',
        'interest_type', 'study_destination', 'study_level', 'study_intake',
        'consent_marketing', 'is_searchable',
    ];

    public function __construct(private LeadService $leads)
    {
    }

    /** Validation rules shared by the web form and the API. */
    public static function rules(bool $partial = false): array
    {
        $sometimes = $partial ? ['sometimes'] : [];
        $keys = fn (string $config) => Rule::in(array_keys(config("talenthub.{$config}")));

        return [
            'name' => [...$sometimes, 'required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'headline' => ['nullable', 'string', 'max:160'],
            'location' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:80'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
            'education_level' => ['nullable', $keys('education_levels')],
            'field_of_study' => ['nullable', 'string', 'max:120'],
            'desired_title' => ['nullable', 'string', 'max:120'],
            'desired_job_type' => ['nullable', Rule::in(array_keys(\App\Enums\JobType::options()))],
            'desired_location' => ['nullable', 'string', 'max:120'],
            'expected_salary' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'availability' => ['nullable', $keys('availabilities')],
            'bio' => ['nullable', 'string', 'max:2000'],
            'skills' => ['nullable'],
            'skills.*' => ['string', 'max:60'],
            'interest_type' => [...$sometimes, 'required', $keys('interest_types')],
            'study_destination' => ['nullable', 'string', 'max:80'],
            'study_level' => ['nullable', $keys('study_levels')],
            'study_intake' => ['nullable', $keys('study_intakes')],
            'consent_marketing' => ['sometimes', 'boolean'],
            'is_searchable' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Save the candidate's profile and refresh everything derived from it:
     * skills, completeness score and the CRM leads.
     */
    public function save(User $user, array $data): CandidateProfile
    {
        return DB::transaction(function () use ($user, $data) {
            if ($userFields = Arr::only($data, ['name', 'phone'])) {
                $user->update($userFields);
            }

            $profile = CandidateProfile::firstOrCreate(['user_id' => $user->id]);
            $profile->fill(Arr::only($data, self::FIELDS))->save();

            if (array_key_exists('skills', $data)) {
                $profile->skills()->sync(Skill::idsFor(Skill::parse($data['skills'])));
            }

            $this->refreshCompleteness($user, $profile);
            $this->syncLeads($user, $profile->refresh());

            return $profile->load('skills');
        });
    }

    /** Recompute and store the completeness percentage. */
    public function refreshCompleteness(User $user, ?CandidateProfile $profile = null): int
    {
        $profile ??= CandidateProfile::firstOrCreate(['user_id' => $user->id]);

        $percent = ProfileCompleteness::calculate(
            $profile->getAttributes(),
            $profile->skills()->count(),
            $user->cvs()->exists(),
            $user->phone,
        );

        $profile->forceFill(['completeness' => $percent])->save();

        return $percent;
    }

    /** Keep the CRM leads in step with the candidate's stated interests. */
    public function syncLeads(User $user, CandidateProfile $profile): void
    {
        $signals = [
            'completeness' => $profile->completeness,
            'has_cv' => $user->cvs()->exists(),
            'consent_marketing' => $profile->consent_marketing,
        ];

        $base = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'country' => $profile->country,
            'source' => 'registration',
        ];

        if (in_array($profile->interest_type, ['job', 'both'], true)) {
            $this->leads->capture(Lead::TYPE_JOB_SEEKER, $base + ['details' => array_filter([
                'desired_title' => $profile->desired_title,
                'location' => $profile->location,
                'years_experience' => $profile->years_experience,
                'education_level' => $profile->education_level,
            ], fn ($v) => $v !== null && $v !== '')], $signals, $user);
        }

        if ($profile->wantsStudyAbroad()) {
            $this->leads->capture(Lead::TYPE_STUDY_ABROAD, $base + ['details' => array_filter([
                'destination' => $profile->study_destination,
                'level' => $profile->study_level,
                'intake' => $profile->study_intake,
                'field_of_study' => $profile->field_of_study,
            ], fn ($v) => $v !== null && $v !== '')], $signals + [
                'destination' => $profile->study_destination,
                'study_level' => $profile->study_level,
            ], $user);
        }
    }
}
