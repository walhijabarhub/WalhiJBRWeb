<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Accepts only link targets that cannot execute script when rendered in an href:
 * absolute http(s) URLs, site-relative paths ("/..."), and in-page anchors ("#...").
 *
 * FILTER_VALIDATE_URL alone is not enough because it accepts any scheme
 * (e.g. "javascript://..."), so the scheme is checked explicitly.
 */
class SafeUrl implements ValidationRule
{
    public function __construct(private readonly bool $allowRelative = true) {}

    public static function isSafe(?string $value, bool $allowRelative = true): bool
    {
        $value = trim((string) $value);
        if ($value === '' || preg_match('/[\x00-\x1F\x7F\s]/', $value)) {
            return false;
        }

        if ($allowRelative) {
            // Site-relative path, but not protocol-relative ("//host") or backslash tricks ("/\host")
            if (preg_match('#^/(?![/\\\\])#', $value) || str_starts_with($value, '#')) {
                return true;
            }
        }

        return preg_match('#^https?://#i', $value) === 1
            && filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Return the URL when it is safe, otherwise the fallback.
     */
    public static function sanitize(?string $value, string $fallback = '#', bool $allowRelative = true): string
    {
        return self::isSafe($value, $allowRelative) ? trim((string) $value) : $fallback;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || ! self::isSafe($value, $this->allowRelative)) {
            $fail('Tautan harus berupa URL http/https yang valid'.($this->allowRelative ? ', jalur yang diawali "/", atau anchor "#".' : '.'));
        }
    }
}
