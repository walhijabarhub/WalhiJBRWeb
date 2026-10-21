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

        $this->assertStringNotContainsString('pecl install', $dockerfile);
        $this->assertMatchesRegularExpression('/^ARG PHPREDIS_SHA256=[0-9a-f]{64}$/m', $dockerfile);
        $this->assertStringContainsString('github.com/phpredis/phpredis/archive/refs/tags/${PHPREDIS_VERSION}.tar.gz', $dockerfile);
        $this->assertStringContainsString('echo "${PHPREDIS_SHA256}  /tmp/phpredis.tar.gz" | sha256sum -c -', $dockerfile);
        $this->assertStringContainsString('docker-php-ext-install redis', $dockerfile);
    }
}
