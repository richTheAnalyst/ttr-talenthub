<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiringRequest extends Model
{
    public const STATUSES = [
        'new' => 'New',
        'accepted' => 'Accepted',
        'in_progress' => 'In progress',
        'fulfilled' => 'Fulfilled',
        'cancelled' => 'Cancelled',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['headcount' => 'integer'];
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}
