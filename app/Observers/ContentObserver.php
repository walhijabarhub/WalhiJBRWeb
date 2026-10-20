<?php

namespace App\Observers;

use App\Models\Content;
use App\Services\Content\ContentAttachment;
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

    /**
     * A soft delete keeps the attachment for restore; it is removed only when the row
     * is deleted permanently.
     */
    public function forceDeleted(Content $content): void
    {
        ContentAttachment::delete($content);
    }

    public function restored(Content $content): void
    {
        ContentCache::flush();
    }
}
