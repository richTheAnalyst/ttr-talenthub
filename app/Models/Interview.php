<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class Interview extends Model
{
    public const PENDING_APPROVAL = 'pending_approval'; // proposed by an employer, waiting for an admin
    public const PROPOSED = 'proposed';                 // sent to the candidate
    public const CONFIRMED = 'confirmed';
    public const DECLINED = 'declined';
    public const CANCELLED = 'cancelled';
    public const REJECTED = 'rejected';                 // an admin did not approve it

    public const MODES = [
        'video' => 'Video call',
        'phone' => 'Phone call',
        'in_person' => 'In person',
    ];

    public const DURATIONS = [15 => '15 minutes', 30 => '30 minutes', 45 => '45 minutes', 60 => '1 hour', 90 => '1 hour 30'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'responded_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    /** Validation shared by the employer and admin forms. */
    public static function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', Rule::in(array_keys(self::DURATIONS))],
            'mode' => ['required', Rule::in(array_keys(self::MODES))],
            'details' => ['required', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** Still on the calendar: waiting for approval, proposed or confirmed. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [self::PENDING_APPROVAL, self::PROPOSED, self::CONFIRMED]);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::PENDING_APPROVAL, self::PROPOSED, self::CONFIRMED], true);
    }

    /** What the candidate is allowed to see (never the ones still waiting for an admin). */
    public function isVisibleToCandidate(): bool
    {
        return in_array($this->status, [self::PROPOSED, self::CONFIRMED, self::DECLINED, self::CANCELLED], true);
    }

    public function modeLabel(): string
    {
        return self::MODES[$this->mode] ?? ucfirst((string) $this->mode);
    }

    /** Ghana is on GMT all year, which is also the app's timezone. */
    public function whenText(): string
    {
        return $this->scheduled_at->format('D j M Y, H:i').' GMT';
    }
}