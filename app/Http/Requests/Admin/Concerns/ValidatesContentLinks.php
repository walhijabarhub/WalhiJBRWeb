<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Rules\SafeUrl;

/**
 * Validation for category-specific fields that end up rendered as links or asset paths
 * on public pages (banner buttons, emergency campaign link, critical-issue icon).
 */
trait ValidatesContentLinks
{
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
