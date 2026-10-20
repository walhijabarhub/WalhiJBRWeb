<?php

namespace App\Services\Content;

use App\Models\Content;
use Illuminate\Support\Facades\Storage;

/**
 * Removes the locally stored file attached to a content entry (its image_url),
 * unless another entry still uses it. Called when an attachment is replaced or
 * removed, and when an entry is force deleted (ContentObserver). A soft delete
 * keeps the file so the entry can be restored.
 */
class ContentAttachment
{
    /**
     * Delete a locally uploaded file if it exists and no other entry uses it.
     * Sanitizes filename with basename() to strictly prevent path traversal.
     * Checks both private 'local' disk and legacy 'public' disk.
     */
    public static function delete(Content $content): void
    {
        $raw = (string) $content->getRawOriginal('image_url');
        if ($raw === '') {
            return;
        }

        $folder = null;
        $filename = null;
        if (str_starts_with($raw, '/storage/uploads/')) {
            $folder = 'uploads/';
            $filename = substr($raw, strlen('/storage/uploads/'));
        } elseif (str_starts_with($raw, 'uploads/')) {
            $folder = 'uploads/';
            $filename = substr($raw, strlen('uploads/'));
        } elseif (str_starts_with($raw, '/storage/documents/')) {
            $folder = 'documents/';
            $filename = substr($raw, strlen('/storage/documents/'));
        } elseif (str_starts_with($raw, 'documents/')) {
            $folder = 'documents/';
            $filename = substr($raw, strlen('documents/'));
        }

        if ($filename === null || $filename === '' || $folder === null) {
            return;
        }

        $cleanFilename = basename($filename);
        if ($cleanFilename === '' || in_array($cleanFilename, ['.', '..'], true)
            || self::isUsedElsewhere($content, $folder.$cleanFilename)) {
            return;
        }

        if ($folder === 'documents/') {
            Storage::disk('local')->delete('documents/'.$cleanFilename);
            Storage::disk('public')->delete('documents/'.$cleanFilename);
        } else {
            Storage::disk('public')->delete('uploads/'.$cleanFilename);
        }
    }

    /**
     * Whether another entry still points at the file, as its attachment or inside its
     * body (Quill images). Soft-deleted entries count too, so a restore keeps its file.
     * LIKE wildcards in the path are left unescaped: they can only widen the match,
     * which keeps a file rather than deleting one that is still in use.
     */
    private static function isUsedElsewhere(Content $content, string $relativePath): bool
    {
        return Content::withTrashed()
            ->whereKeyNot($content->getKey())
            ->where(fn ($q) => $q->where('image_url', 'like', '%'.$relativePath)
                ->orWhere('body', 'like', '%'.$relativePath.'%'))
            ->exists();
    }
}
