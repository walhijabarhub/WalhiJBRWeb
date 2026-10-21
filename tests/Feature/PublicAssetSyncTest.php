<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The app container copies public/ into the volume nginx serves. cp never deletes, so
 * the entrypoint must also remove files that are no longer in the image (except build/).
 */
class PublicAssetSyncTest extends TestCase
{
    public function test_entrypoint_prunes_files_removed_from_the_image(): void
    {
        $script = file_get_contents(base_path('docker-entrypoint.sh'));
        $sync = substr($script, strpos($script, '# ── Sync public assets'));

        // Triggered by a fingerprint of public/ (not by public/build's mtime, which a cached
        // frontend layer keeps unchanged), and copied without -u so a rollback wins.
        $this->assertStringContainsString("stat -c '%n %s %Y'", $sync);
        $this->assertStringContainsString('!= "$PUBLIC_FINGERPRINT"', $sync);
        $this->assertStringNotContainsString(' -nt ', $sync);
        $this->assertStringContainsString('cp -r /var/www/html/public/. /var/www/html/public_shared/', $sync);
        $this->assertStringNotContainsString('cp -ru', $sync);
        $this->assertMatchesRegularExpression("#find \\. -type f ! -path './build/\\*' ! -name '\\.assets_synced'#", $sync);
        $this->assertStringContainsString('[ -e "/var/www/html/public/$f" ] || [ -L "/var/www/html/public/$f" ] || rm -f -- "$f"', $sync);
        $this->assertLessThan(strpos($sync, 'echo "$PUBLIC_FINGERPRINT" > /var/www/html/public_shared/.assets_synced'), strpos($sync, 'rm -f --'));
    }
}
