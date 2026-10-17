<?php

namespace App\Models;

use App\Enums\LeadStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    public const TYPE_JOB_SEEKER = 'job_seeker';
    public const TYPE_STUDY_ABROAD = 'study_abroad';
    public const TYPE_EMPLOYER = 'employer';

    public const TYPES = [
        self::TYPE_JOB_SEEKER => 'Job seeker',
        self::TYPE_STUDY_ABROAD => 'Study abroad',
        self::TYPE_EMPLOYER => 'Employer',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'stage' => LeadStage::class,
            'details' => 'array',
            'score' => 'integer',
            'last_contacted_at' => 'datetime',
            'crm_synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }
}
