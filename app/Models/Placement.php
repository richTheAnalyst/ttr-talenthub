<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A hire made through TTR. The commission is a percentage of the monthly salary. */
class Placement extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'monthly_salary' => 'integer',
            'commission_rate' => 'float',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'client_monthly' => 'integer',
            'one_time_fee' => 'integer',
            'fee_paid_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isRecruitment(): bool
    {
        return $this->engagement === 'recruitment';
    }

    /**
     * Outsourcing: what TTR keeps each month. With a client budget it is the budget minus the worker's pay;
     * otherwise the percentage of the salary. Null for recruitment or when nothing is set.
     */
    public function monthlyFee(): ?float
    {
        if ($this->isRecruitment()) {
            return null;
        }

        if ($this->client_monthly !== null && $this->monthly_salary !== null) {
            return round($this->client_monthly - $this->monthly_salary, 2);
        }

        return $this->monthly_salary === null ? null : round($this->monthly_salary * $this->commission_rate / 100, 2);
    }
}