<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobAlert extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'min_match' => 'integer',
            'last_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Does this job satisfy the alert's keyword, location and type filters? */
    public function matchesFilters(JobPosting $job): bool
    {
        if ($this->job_type && $job->job_type?->value !== $this->job_type) {
            return false;
        }

        if ($this->location && stripos((string) $job->location, $this->location) === false) {
            return false;
        }

        $keywords = array_filter(array_map('trim', preg_split('/[,;]+/', (string) $this->keywords)));
        if ($keywords === []) {
            return true;
        }

        $haystack = mb_strtolower($job->title.' '.$job->description);
        foreach ($keywords as $keyword) {
            if (str_contains($haystack, mb_strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    public function summary(): string
    {
        $parts = array_filter([
            $this->keywords ?: null,
            $this->location ?: null,
            $this->job_type ? ucwords(str_replace('_', ' ', $this->job_type)) : null,
        ]);

        return $parts ? implode(', ', $parts) : 'Every new job';
    }
}
