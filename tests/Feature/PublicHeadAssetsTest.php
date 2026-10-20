<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicHeadAssetsTest extends TestCase
{
    /**
     * Public pages load fonts, the Vite bundle and Lucide only through
     * partials/head-assets, so a change there reaches every page.
     */
    public function test_public_pages_use_the_shared_head_partial(): void
    {
        $offenders = [];

        foreach (glob(resource_path('views/*.blade.php')) as $view) {
            $html = file_get_contents($view);
            if (! str_contains($html, '<head>')) {
                continue;
            }

            $name = basename($view);
            if (! str_contains($html, "@include('partials.head-assets'")) {
                $offenders[] = "{$name}: missing partials.head-assets";
            }
            foreach (['fonts.googleapis.com', 'font-face.css', '@vite(', 'lucide.min.js'] as $duplicate) {
                if (str_contains($html, $duplicate)) {
                    $offenders[] = "{$name}: loads {$duplicate} itself";
                }
            }
        }

        $this->assertSame([], $offenders);
    }
}
