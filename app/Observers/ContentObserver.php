<?php

namespace App\Observers;

use App\Models\Content;
use App\Services\Content\ContentCache;

class ContentObserver
{
    public function saved(Content $content): void
    {
        // View counter updates do not change what the cached pages show.
        if ($content->wasChanged() && array_keys($content->getChanges()) === ['views']) {
            return;
        }

        ContentCache::flush();
    }

    public function deleted(Content $content): void
    {
        ContentCache::flush();
    }

    public function restored(Content $content): void
    {
        ContentCache::flush();
    }
}
