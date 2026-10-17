<?php

namespace App\Services\Content;

use App\Models\Content;
use Closure;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Cache keys for public pages built from Content, invalidated by ContentObserver
 * whenever content is created, updated, deleted or restored.
 */
class ContentCache
{
    // ":v2" ignores entries written by the earlier version, which stored Eloquent objects.
    public const HOME = 'content:home-sections:v2';

    public const SITEMAP = 'content:sitemap:v2';

    /** Keys owned by the global view composers in AppServiceProvider. */
    private const GLOBAL_KEYS = ['global_contact', 'global_campaign'];

    /**
     * Cache Content models as plain attribute arrays and hydrate them on read.
     *
     * Laravel 13 refuses to unserialize objects from the cache by default
     * (cache.serializable_classes = false), so Eloquent models must never be
     * stored directly. The loader returns a model, a collection of models, null,
     * or an array of those (one level deep).
     */
    public static function remember(string $key, DateTimeInterface|int $ttl, Closure $loader): mixed
    {
        $cached = Cache::remember($key, $ttl, fn () => self::dehydrate($loader()));

        return self::hydrate($cached);
    }

    private static function dehydrate(mixed $value): mixed
    {
        return match (true) {
            $value instanceof Content => ['__content' => $value->getAttributes()],
            $value instanceof Collection => ['__contents' => $value->map(fn (Content $c) => $c->getAttributes())->values()->all()],
            is_array($value) => array_map(self::dehydrate(...), $value),
            default => $value,
        };
    }

    private static function hydrate(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_key_exists('__content', $value)) {
            return Content::hydrate([$value['__content']])->first();
        }

        if (array_key_exists('__contents', $value)) {
            return Content::hydrate($value['__contents']);
        }

        return array_map(self::hydrate(...), $value);
    }

    public static function flush(): void
    {
        foreach ([self::HOME, self::SITEMAP, ...self::GLOBAL_KEYS] as $key) {
            Cache::forget($key);
        }
    }
}
