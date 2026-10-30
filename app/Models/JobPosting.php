<?php

namespace App\Models;

use App\Enums\JobType;
use App\Support\Db;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosting extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';

    protected $table = 'job_postings';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'job_type' => JobType::class,
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'approved_at' => 'datetime',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'experience_min' => 'integer',
            'client_budget' => 'integer',
            'placement_fee' => 'integer',
            'fee_rate' => 'float',
        ];
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'job_skill', 'job_posting_id', 'skill_id')
            ->withPivot('is_required');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'job_posting_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN)
            ->where('approval_status', self::APPROVAL_APPROVED)
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /** Apply the public search filters (q, type, location). */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $like = Db::like();

        if (! empty($filters['q'])) {
            $term = Db::contains($filters['q']);
            $query->where(fn (Builder $q) => $q
                ->where('title', $like, $term)
                ->orWhere('description', $like, $term)
                ->orWhereHas('employer', fn (Builder $e) => $e->where('company_name', $like, $term)));
        }

        if (! empty($filters['type'])) {
            $query->where('job_type', $filters['type']);
        }

        if (! empty($filters['location'])) {
            $query->where('location', $like, Db::contains($filters['location']));
        }

        return $query;
    }

    public const ENGAGEMENTS = ['outsourcing' => 'Outsourcing', 'recruitment' => 'Recruitment'];

    public function isOutsourcing(): bool
    {
        return $this->engagement === 'outsourcing';
    }

    /** What the worker earns after TTR keeps its percentage of the client's budget. */
    public function workerPay(float $rate): int
    {
        return (int) floor(((int) $this->client_budget) * (100 - $rate) / 100);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN
            && $this->approval_status === self::APPROVAL_APPROVED
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /** Published by the employer but not yet approved by an admin. */
    public function isAwaitingApproval(): bool
    {
        return $this->status === self::STATUS_OPEN && $this->approval_status === self::APPROVAL_PENDING;
    }

    public function salaryRange(): ?string
    {
        if (! $this->salary_min && ! $this->salary_max) {
            return null;
        }

        $fmt = fn (int $n) => number_format($n);

        if ($this->salary_min && $this->salary_max) {
            return "{$this->currency} {$fmt($this->salary_min)} to {$fmt($this->salary_max)} a month";
        }

        return $this->salary_min
            ? "From {$this->currency} {$fmt($this->salary_min)} a month"
            : "Up to {$this->currency} {$fmt($this->salary_max)} a month";
    }

    /** Plain-array view of this job for the matching engine. Skills must be loaded. */
    public function matchPayload(): array
    {
        $required = [];
        $optional = [];

        foreach ($this->skills as $skill) {
            if ($skill->pivot->is_required) {
                $required[] = $skill->name;
            } else {
                $optional[] = $skill->name;
            }
        }

        return [
            'title' => $this->title,
            'location' => $this->location,
            'country' => $this->country,
            'experience_min' => (int) $this->experience_min,
            'education_level' => $this->education_level,
            'job_type' => $this->job_type?->value,
            'skills_required' => $required,
            'skills_optional' => $optional,
        ];
    }
}