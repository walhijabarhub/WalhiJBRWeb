<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Every bundled file referenced literally from code (asset('assets/...'), '/assets/...',
 * url('/iqon/...')) must exist in public/, so pruning unused theme files cannot break a page.
 */
class BundledAssetsExistTest extends TestCase
{
    public function test_literal_asset_references_exist(): void
    {
        $missing = [];
        $paths = [resource_path('views'), app_path(), database_path('seeders'), resource_path('css'), resource_path('js')];

        foreach ($paths as $path) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) as $file) {
                preg_match_all('#(?<![\w.-])/?((?:assets|iqon)/[\w./-]+\.(?:png|jpe?g|svg|webp|gif|ico|css|js|woff2?))\b#', file_get_contents($file->getPathname()), $m);
                foreach ($m[1] as $reference) {
                    if (! is_file(public_path($reference))) {
                        $missing[] = str_replace(base_path().'/', '', $file->getPathname()).': '.$reference;
                    }
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)));
    }
}
