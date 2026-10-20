<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * resources/js/icons.js bundles only the Lucide icons that are imported there, so every
 * data-lucide name used in a view (or set from PHP/JS) must be in that list.
 */
class LucideIconsTest extends TestCase
{
    use RefreshDatabase;

    /** Icon names that are not written literally in a data-lucide attribute. */
    private const DYNAMIC_ICONS = [
        'heart', 'message-square', 'file-text', 'send', // AdminController::getRecentActivities()
        'panel-left', 'panel-left-close',               // admin sidebar toggle
    ];

    public function test_every_used_icon_is_bundled(): void
    {
        $used = collect(self::DYNAMIC_ICONS);
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                preg_match_all('/data-lucide="([a-z0-9-]+)"/', file_get_contents($file->getPathname()), $m);
                $used = $used->merge($m[1]);
            }
        }

        $bundle = file_get_contents(resource_path('js/icons.js'));
        $missing = $used->unique()
            ->reject(fn (string $name) => preg_match('/^\s+'.Str::studly($name).',$/m', $bundle))
            ->values()
            ->all();

        $this->assertSame([], $missing, 'Add these icons to resources/js/icons.js.');
    }

    public function test_no_page_loads_lucide_from_a_cdn(): void
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
                $this->assertStringNotContainsString('unpkg.com/lucide', file_get_contents($file->getPathname()), $file->getFilename());
            }
        }

        $csp = $this->get('/')->assertOk()->headers->get('Content-Security-Policy');
        $this->assertStringNotContainsString('unpkg.com', $csp);
    }
}
