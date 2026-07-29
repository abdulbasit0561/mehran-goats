<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    public $timestamps = true;

    /**
     * Cache tag for all settings.
     */
    private const CACHE_KEY = 'site_settings_all';

    /**
     * Get a single setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::allAsArray()[$key] ?? $default;
    }

    /**
     * Set a single setting value (create or update).
     */
    public static function set(string $key, mixed $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
        self::flushCache();
    }

    /**
     * Get all settings as an associative array [key => value].
     * Cached for 5 minutes — flushed on any write.
     */
    public static function allAsArray(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function () {
            return self::query()->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Flush the settings cache.
     */
    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Boot — clear cache on any model event.
     */
    protected static function booted(): void
    {
        static::saved(fn () => self::flushCache());
        static::deleted(fn () => self::flushCache());
    }
}
