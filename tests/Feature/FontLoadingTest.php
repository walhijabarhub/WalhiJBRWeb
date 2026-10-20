<?php

namespace Tests\Feature;

use Tests\TestCase;

class FontLoadingTest extends TestCase
{
    /**
     * Montserrat is loaded by a <link> in each page head; an @import in app.css
     * would download it a second time and block rendering.
     */
    public function test_app_css_does_not_import_fonts(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertDoesNotMatchRegularExpression('/@import\s+url\([\'"]?https:\/\/fonts\./', $css);
    }

    public function test_every_page_using_app_css_links_its_fonts(): void
    {
        $missing = [];

        foreach (glob(resource_path('views/{,*/,*/*/}*.blade.php'), GLOB_BRACE) as $view) {
            $html = file_get_contents($view);
            if (! str_contains($html, 'resources/css/app.css') || ! str_contains($html, '<head>')) {
                continue;
            }
            if (! str_contains($html, 'fonts.googleapis.com/css2?family=Montserrat')) {
                $missing[] = str_replace(resource_path('views/'), '', $view);
            }
        }

        $this->assertSame([], $missing, 'Pages without the Montserrat font link.');
    }
}
