<?php

namespace App\Services\Content;

use Illuminate\Support\Facades\Cache;

/**
 * Cache keys for public pages built from Content, invalidated by ContentObserver
 * whenever content is created, updated, deleted or restored.
 */
class ContentCache
{
    public const HOME = 'content:home-sections';

    public const SITEMAP = 'content:sitemap';

    /** Keys owned by the global view composers in AppServiceProvider. */
    private const GLOBAL_KEYS = ['global_contact', 'global_campaign'];

    public static function flush(): void
    {
        foreach ([self::HOME, self::SITEMAP, ...self::GLOBAL_KEYS] as $key) {
            Cache::forget($key);
        }
    }
}
