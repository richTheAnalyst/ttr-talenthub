<?php

namespace App\Enums;

enum LeadStage: string
{
    use Options;

    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Converted = 'converted';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::Qualified => 'Qualified',
            self::Converted => 'Converted',
            self::Lost => 'Lost',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::New => 'blue',
            self::Contacted => 'amber',
            self::Qualified => 'violet',
            self::Converted => 'green',
            self::Lost => 'slate',
        };
    }
}
