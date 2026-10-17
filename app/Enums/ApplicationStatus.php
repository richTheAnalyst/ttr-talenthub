<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    use Options;

    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Shortlisted = 'shortlisted';
    case Interview = 'interview';
    case Offered = 'offered';
    case Hired = 'hired';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under review',
            self::Shortlisted => 'Shortlisted',
            self::Interview => 'Interview',
            self::Offered => 'Offer made',
            self::Hired => 'Hired',
            self::Rejected => 'Not selected',
            self::Withdrawn => 'Withdrawn',
        };
    }

    /** CSS class suffix used by the badge component. */
    public function badge(): string
    {
        return match ($this) {
            self::Submitted, self::Withdrawn => 'slate',
            self::UnderReview => 'blue',
            self::Shortlisted => 'violet',
            self::Interview => 'amber',
            self::Offered => 'teal',
            self::Hired => 'green',
            self::Rejected => 'red',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Hired, self::Rejected, self::Withdrawn], true);
    }

    /**
     * The happy-path stops shown on the application tracker, in order.
     *
     * @return list<self>
     */
    public static function pipeline(): array
    {
        return [self::Submitted, self::UnderReview, self::Shortlisted, self::Interview, self::Offered, self::Hired];
    }

    /**
     * Statuses an employer may move an application to.
     *
     * @return array<string,string>
     */
    public static function employerChoices(): array
    {
        $choices = [];
        foreach ([self::UnderReview, self::Shortlisted, self::Interview, self::Offered, self::Hired, self::Rejected] as $case) {
            $choices[$case->value] = $case->label();
        }

        return $choices;
    }
}
