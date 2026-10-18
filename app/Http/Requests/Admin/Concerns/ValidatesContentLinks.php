<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Models\Content;
use App\Rules\SafeUrl;
use Illuminate\Support\Facades\Storage;

/**
 * Validation for category-specific fields that end up rendered as links or asset paths
 * on public pages (banner buttons, emergency campaign link, critical-issue icon), and
 * for manually typed attachment paths.
 */
trait ValidatesContentLinks
{
    /**
     * A typed image_url may not point at a private document (SEC-008). The download
     * endpoint resolves any local, non-"uploads/" path to documents/<basename>, so a
     * typed path could otherwise publish another entry's draft or admin-only document
     * through this entry's download link. Only the entry's own current path may be
     * sent back unchanged (the edit form re-submits it).
     */
    protected function privateDocumentPathRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            $val = is_string($value) ? trim($value) : '';
            if ($val === '' || preg_match('#^https?://#i', $val) || str_contains($val, 'uploads/')) {
                return;
            }

            $content = $this->route('content');
            if ($content instanceof Content && in_array($val, [(string) $content->getRawOriginal('image_url'), $content->image_url], true)) {
                return;
            }

            $document = 'documents/'.basename($val);
            if (preg_match('#^/?(storage/)?documents/#i', $val)
                || Storage::disk('local')->exists($document)
                || Storage::disk('public')->exists($document)) {
                $fail('Dokumen privat tidak dapat ditautkan lewat URL. Unggah berkasnya langsung pada entri ini.');
            }
        };
    }

    protected function contentLinkRules(): array
    {
        $rules = [
            'banner_btn1_text' => ['nullable', 'string', 'max:60'],
            'banner_btn1_url' => ['nullable', 'string', 'max:500', new SafeUrl],
            'banner_btn2_text' => ['nullable', 'string', 'max:60'],
            'banner_btn2_url' => ['nullable', 'string', 'max:500', new SafeUrl],
            'isu_icon' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_\-]+\.(svg|png)$/'],
            'isu_badge' => ['nullable', 'string', 'max:60'],
        ];

        // The emergency campaign stores its target URL in "tags" and renders it in the site header.
        if ($this->route('category') === 'kampanye-darurat') {
            $rules['tags'] = ['nullable', 'string', 'max:500', new SafeUrl];
        }

        // Banners keep "text1|url1|text2|url2" in "tags"; validate the URLs even when the
        // value is posted directly instead of through the banner_btn* fields.
        if ($this->route('category') === 'banner') {
            $rules['tags'] = ['nullable', 'string', 'max:500', function (string $attribute, mixed $value, \Closure $fail) {
                $parts = explode('|', (string) $value);
                foreach ([1, 3] as $index) {
                    if (! empty($parts[$index]) && ! SafeUrl::isSafe($parts[$index])) {
                        $fail('Tautan tombol banner harus berupa URL http/https, jalur "/", atau anchor "#".');

                        return;
                    }
                }
            }];
        }

        return $rules;
    }
}
