<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employer extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }

    public function hiringRequests(): HasMany
    {
        return $this->hasMany(HiringRequest::class);
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
