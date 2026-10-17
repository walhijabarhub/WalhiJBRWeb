<?php

namespace App\Models;

use App\Enums\ContentCategory;
use App\Enums\ContentStatus;
use App\Services\Content\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Content extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'body',
        'tags',
        'status',
        'image_url',
        'publish_date',
        'category',
        'is_promoted',
        'author',
        'views',
    ];

    protected $casts = [
        'publish_date' => 'date:Y-m-d',
        'is_promoted' => 'boolean',
        'views' => 'integer',
    ];

    /**
     * Categories containing sensitive organizational configurations that require admin role.
     */
    public const SENSITIVE_CATEGORIES = [
        'kontak',
        'kampanye-darurat',
        'donasi',
    ];

    /**
     * Determine if a category is considered sensitive organizational configuration.
     */
    public static function isSensitiveCategory(?string $category): bool
    {
        return in_array($category, self::SENSITIVE_CATEGORIES, true);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────────────────────

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function donations()
    {
        return $this->hasMany(Donation::class, 'campaign_id');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Eloquent Scopes
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Filter only published content.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published->value);
    }

    /**
     * Filter by a single category or an array of categories.
     *
     * @param  string|ContentCategory|string[]|ContentCategory[]  $category
     */
    public function scopeOfCategory(Builder $query, string|ContentCategory|array $categories): Builder
    {
        // Normalise to array of string values
        $values = collect((array) $categories)->map(function ($cat) {
            return $cat instanceof ContentCategory ? $cat->value : $cat;
        })->all();

        return count($values) === 1
            ? $query->where('category', $values[0])
            : $query->whereIn('category', $values);
    }

    /**
     * Search title, body and tags.
     *
     * Uses the FULLTEXT index on MySQL/MariaDB (boolean mode, every word required,
     * prefix match) and falls back to LIKE on other drivers or when every word is
     * shorter than the InnoDB minimum token size.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $words = collect(preg_split('/\s+/u', preg_replace('/[+\-<>()~*"@]+/u', ' ', $term)))
            ->filter(fn ($word) => mb_strlen($word) >= 3)
            ->values();

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
        $driver = $query->getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true) && $words->isNotEmpty()) {
            $boolean = $words->map(fn ($word) => '+'.$word.'*')->implode(' ');

            return $query->where(fn ($q) => $q
                ->whereFullText(['title', 'body'], $boolean, ['mode' => 'boolean'])
                ->orWhere('tags', 'like', $like));
        }

        return $query->where(fn ($q) => $q
            ->where('title', 'like', $like)
            ->orWhere('body', 'like', $like)
            ->orWhere('tags', 'like', $like));
    }

    /**
     * Filter only promoted content.
     */
    public function scopePromoted(Builder $query): Builder
    {
        return $query->where('is_promoted', true);
    }

    /**
     * Filter by publishable editorial categories.
     */
    public function scopePublishable(Builder $query): Builder
    {
        return $query->whereIn('category', ContentCategory::publishableCategoryValues());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Get the properly formatted image/file URL.
     */
    public function getImageUrlAttribute(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $value = trim($value);

        // Explicitly block dangerous URI schemes
        if (preg_match('/^(javascript|vbscript|data):/i', $value)) {
            return null;
        }

        // Strictly block protocol-relative URLs (//evil.example, ///evil.example)
        if (str_starts_with($value, '//')) {
            return null;
        }

        // Valid external HTTP / HTTPS URLs
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return filter_var($value, FILTER_VALIDATE_URL) ? $value : null;
        }

        // Positive allowlist for local storage and static assets
        if (str_starts_with($value, '/storage/')) {
            return $value;
        }

        if (str_starts_with($value, 'storage/')) {
            return '/'.$value;
        }

        if (str_starts_with($value, '/documents/')) {
            return '/storage'.$value;
        }

        if (str_starts_with($value, 'documents/')) {
            return '/storage/'.$value;
        }

        if (str_starts_with($value, '/uploads/')) {
            return '/storage'.$value;
        }

        if (str_starts_with($value, 'uploads/')) {
            return '/storage/'.$value;
        }

        if (str_starts_with($value, '/assets/')) {
            return $value;
        }

        if (str_starts_with($value, 'assets/')) {
            return '/'.$value;
        }

        // Safe alphanumeric relative file path fallback (without leading slash)
        if (preg_match('/^[a-zA-Z0-9_\-\.\/]+$/', $value) && ! str_starts_with($value, '/')) {
            return '/storage/'.$value;
        }

        return null;
    }

    /**
     * Check whether the attached media is a document rather than a display image.
     */
    public function isDocument(): bool
    {
        $raw = (string) $this->getRawOriginal('image_url');
        if ($raw === '') {
            return false;
        }

        $clean = parse_url($raw, PHP_URL_PATH) ?? $raw;
        $ext = strtolower(pathinfo($clean, PATHINFO_EXTENSION));
        $docExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'rtf', 'csv', 'txt'];

        if (in_array($ext, $docExtensions, true)) {
            return true;
        }

        if (str_starts_with($raw, 'documents/') || str_starts_with($raw, '/storage/documents/')) {
            return true;
        }

        return false;
    }

    /**
     * Get the download URL for documents, or direct external/image URL.
     * Guardrail: External URLs are rendered directly (no open redirector).
     */
    public function getDownloadUrlAttribute(): ?string
    {
        $raw = (string) $this->getRawOriginal('image_url');
        if ($raw === '') {
            return null;
        }

        // If external URL, return direct validated URL (never through redirector)
        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            return filter_var($raw, FILTER_VALIDATE_URL) ? $raw : null;
        }

        // If it's a local document, route through the authorized download endpoint
        if ($this->isDocument()) {
            return route('documents.download', $this);
        }

        return $this->image_url;
    }

    /**
     * Return a plain-text excerpt (max 155 chars) stripped of HTML.
     */
    public function getExcerptAttribute($value = null, int $length = 155): string
    {
        return Str::limit(strip_tags($this->body ?? ''), $length);
    }

    /**
     * Estimated reading time in minutes (200 wpm average).
     */
    public function getReadTimeAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->body ?? ''));

        return max(1, (int) ceil($wordCount / 200));
    }

    /**
     * Sanitized HTML body to prevent XSS while safely supporting HTML5 structure and clean plain-text fallback.
     */
    public function getSanitizedBodyAttribute(): string
    {
        $raw = trim($this->body ?? '');
        if ($raw === '') {
            return '';
        }

        // If body has no HTML tags at all, format plain text with paragraphs and line breaks
        if (strip_tags($raw) === $raw) {
            $paragraphs = preg_split('/(\r\n|\n|\r){2,}/', $raw);
            $formatted = '';
            foreach ($paragraphs as $p) {
                $p = trim($p);
                if ($p !== '') {
                    $formatted .= '<p>'.nl2br(htmlspecialchars($p, ENT_QUOTES, 'UTF-8')).'</p>';
                }
            }
            $raw = $formatted ?: '<p>'.nl2br(htmlspecialchars($raw, ENT_QUOTES, 'UTF-8')).'</p>';
        }

        return HtmlSanitizer::purify($raw);
    }

    /**
     * Attributes for the admin edit modal. The body is sent already sanitized so that
     * loading it into the WYSIWYG editor can never execute stored markup.
     */
    public function toEditorArray(): array
    {
        return array_merge($this->attributesToArray(), [
            'body' => HtmlSanitizer::forStorage($this->body),
        ]);
    }
}
