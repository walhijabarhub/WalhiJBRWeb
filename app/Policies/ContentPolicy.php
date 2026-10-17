<?php

namespace App\Policies;

use App\Enums\ContentCategory;
use App\Models\Content;
use App\Models\User;

class ContentPolicy
{
    /**
     * Determine whether the user can view any contents.
     */
    public function viewAny(User $user): bool
    {
        return $user->canManageContent();
    }

    /**
     * Whether the user may open the admin listing for a category.
     * Sensitive categories (e.g. the donation dashboard with donor data) are admin-only.
     */
    public function viewCategory(User $user, string $category): bool
    {
        if (! ContentCategory::tryFrom($category)) {
            return false;
        }

        if (Content::isSensitiveCategory($category)) {
            return $user->isAdmin();
        }

        return $user->canManageContent();
    }

    /**
     * Determine whether the user (or guest) can view the content.
     */
    public function view(?User $user, Content $content): bool
    {
        if (! ContentCategory::tryFrom($content->category)) {
            return false;
        }

        // Published content is accessible to anyone, including guests
        if ($content->status === 'published') {
            return true;
        }

        // Non-published content (draft/archived) requires an authenticated user
        if ($user === null) {
            return false;
        }

        // Sensitive categories require admin privileges even for non-published content
        if (Content::isSensitiveCategory($content->category)) {
            return $user->isAdmin();
        }

        return $user->canManageContent();
    }

    /**
     * Determine whether the user can create contents.
     */
    public function create(User $user, ?string $category = null): bool
    {
        if ($category !== null) {
            $catEnum = ContentCategory::tryFrom($category);
            if (! $catEnum) {
                return false;
            }

            if (Content::isSensitiveCategory($category)) {
                return $user->isAdmin();
            }
        }

        return $user->canManageContent();
    }

    /**
     * Determine whether the user can update the content.
     */
    public function update(User $user, Content $content): bool
    {
        if (! ContentCategory::tryFrom($content->category)) {
            return false;
        }

        if (Content::isSensitiveCategory($content->category)) {
            return $user->isAdmin();
        }

        return $user->canManageContent();
    }

    /**
     * Determine whether the user can delete the content.
     */
    public function delete(User $user, Content $content): bool
    {
        if (! ContentCategory::tryFrom($content->category)) {
            return false;
        }

        return $user->canDelete();
    }
}
