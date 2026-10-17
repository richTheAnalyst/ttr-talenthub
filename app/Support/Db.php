<?php

namespace App\Support;

class Db
{
    /** Case-insensitive LIKE operator for the active database driver. */
    public static function like(): string
    {
        return \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    /** Wrap a search term for LIKE, neutralising wildcard characters. */
    public static function contains(string $term): string
    {
        return '%'.trim(preg_replace('/\s+/', ' ', str_replace(['%', '_'], ' ', $term))).'%';
    }
}