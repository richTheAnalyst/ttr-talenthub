<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** Small key/value store for admin-editable settings. */
class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public static function read(string $key, ?string $default = null): ?string
    {
        return Cache::remember("setting:{$key}", 300, fn () => static::whereKey($key)->value('value')) ?? $default;
    }

    public static function write(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");
    }
}