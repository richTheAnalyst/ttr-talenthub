<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'status_changed_at' => 'datetime',
            'match_score' => 'integer',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_posting_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class)->oldest();
    }
}
