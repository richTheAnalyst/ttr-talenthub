<?php

namespace App\Enums;

/**
 * Adds a value => label map to string-backed enums that define label().
 */
trait Options
{
    /** @return array<string,string> */
    public static function options(): array
    {
        $options = [];
        foreach (static::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
