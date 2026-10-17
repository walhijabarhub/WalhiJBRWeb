<?php

namespace App\Services\Content;

use App\Models\Content;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Buffers page/download views in the cache and writes them to the database in
 * batches (walhi:flush-views), instead of one UPDATE per page view.
 */
class ViewCounter
{
    private const INDEX_KEY = 'content-views:index';

    private const LOCK_KEY = 'content-views:lock';

    public static function record(Content $content): void
    {
        $key = self::counterKey($content->id);

        // The first increment in a flush window registers the id in the index.
        if ((int) Cache::increment($key) === 1) {
            Cache::lock(self::LOCK_KEY, 5)->block(3, function () use ($content) {
                $ids = Cache::get(self::INDEX_KEY, []);
                $ids[$content->id] = true;
                Cache::forever(self::INDEX_KEY, $ids);
            });
        }
    }

    /**
     * Write buffered views to the database.
     *
     * @return int number of views written
     */
    public static function flush(): int
    {
        $ids = Cache::lock(self::LOCK_KEY, 5)->block(3, function () {
            return array_keys(Cache::pull(self::INDEX_KEY, []));
        });

        $written = 0;
        foreach ($ids as $id) {
            $count = (int) Cache::pull(self::counterKey($id), 0);
            if ($count > 0) {
                // Query builder: no model events, so caches are not flushed for a counter.
                DB::table('contents')->where('id', $id)->increment('views', $count);
                $written += $count;
            }
        }

        return $written;
    }

    /**
     * Views recorded but not yet written to the database.
     */
    public static function pending(int $contentId): int
    {
        return (int) Cache::get(self::counterKey($contentId), 0);
    }

    private static function counterKey(int $id): string
    {
        return 'content-views:'.$id;
    }
}
