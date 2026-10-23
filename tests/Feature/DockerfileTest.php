<?php

namespace Tests\Feature;

use Tests\TestCase;

class DockerfileTest extends TestCase
{
    /**
     * Image builds (and therefore deploys) must not depend on pecl.php.net: phpredis
     * comes from its GitHub release tarball, verified against a pinned SHA-256.
     */
    public function test_phpredis_is_built_from_a_checksum_pinned_tarball(): void
    {
        $dockerfile = file_get_contents(base_path('Dockerfile'));

        // No command line may run pecl (comments may still mention it).
        $this->assertDoesNotMatchRegularExpression('/^\s*(RUN\s+|&&\s+)pecl\s+install/m', $dockerfile);
        $this->assertMatchesRegularExpression('/^ARG PHPREDIS_SHA256=[0-9a-f]{64}$/m', $dockerfile);
        $this->assertStringContainsString('github.com/phpredis/phpredis/archive/refs/tags/${PHPREDIS_VERSION}.tar.gz', $dockerfile);
        $this->assertStringContainsString('echo "${PHPREDIS_SHA256}  /tmp/phpredis.tar.gz" | sha256sum -c -', $dockerfile);
        $this->assertStringContainsString('docker-php-ext-install redis', $dockerfile);
    }

    /**
     * A floating tag (php:8.4-fpm-alpine3.22) silently froze on a vulnerable PHP release.
     * The base must name an exact patch release, at or above the CVE-2026-109618/109619
     * fix, and be pinned to a digest.
     */
    public function test_php_base_image_is_pinned_to_a_patched_release_and_digest(): void
    {
        $dockerfile = file_get_contents(base_path('Dockerfile'));

        $this->assertMatchesRegularExpression(
            '/^FROM php:(\d+\.\d+\.\d+)-fpm-alpine[\d.]+@sha256:[0-9a-f]{64}$/m',
            $dockerfile
        );
        preg_match('/^FROM php:(\d+\.\d+\.\d+)-fpm-alpine/m', $dockerfile, $m);
        $this->assertTrue(version_compare($m[1], '8.4.27', '>='), "PHP base {$m[1]} is older than 8.4.27");

        $this->assertStringContainsString('COPY docker/php/security.ini', $dockerfile);
        $this->assertMatchesRegularExpression(
            '/^session\.upload_progress\.enabled\s*=\s*0$/m',
            file_get_contents(base_path('docker/php/security.ini'))
        );
    }
}
