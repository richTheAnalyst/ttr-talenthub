<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One month of money for an outsourcing placement: what the employer paid TTR and what TTR paid the worker. */
class PlacementPayment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'period' => 'date',
            'client_amount' => 'integer',
            'worker_amount' => 'integer',
            'client_paid_at' => 'datetime',
            'worker_paid_at' => 'datetime',
        ];
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }
}