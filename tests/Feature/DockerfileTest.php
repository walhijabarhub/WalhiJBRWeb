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

    /**
     * Dependencies are resolved for the PHP release production runs. Without this,
     * Composer (and Dependabot) resolve for the lowest PHP allowed by "php": "^8.3",
     * which downgraded Symfony 8 to 7.4 and pulled in Guzzle 8 in a Laravel patch bump.
     */
    public function test_composer_platform_php_matches_the_docker_base_image(): void
    {
        preg_match('/^FROM php:(\d+\.\d+\.\d+)-fpm/m', file_get_contents(base_path('Dockerfile')), $m);
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);

        $this->assertSame($m[1], $composer['config']['platform']['php'] ?? null);
    }

    /**
     * Compilers, headers and download tools are a virtual package removed after the
     * extensions are built, so they never reach the runtime image.
     */
    public function test_build_tools_are_not_kept_in_the_runtime_image(): void
    {
        $dockerfile = file_get_contents(base_path('Dockerfile'));

        $this->assertMatchesRegularExpression('/apk add --no-cache --virtual \.build-deps\s*\\\\\s*\$PHPIZE_DEPS/', $dockerfile);
        $this->assertMatchesRegularExpression('/opcache \\\\\s*&& apk del \.build-deps/', $dockerfile);
        $this->assertStringContainsString('apk del .composer-deps', $dockerfile);

        // Runtime layer: no -dev packages, git or compilers outside the virtual packages.
        preg_match('/^RUN apk add --no-cache \\\\\n(.*?)&& apk add --no-cache --virtual/ms', $dockerfile, $runtime);
        $this->assertNotEmpty($runtime);
        $this->assertDoesNotMatchRegularExpression('/-dev\b|\bgit\b|\bcurl\b|\bgcc\b/', $runtime[1]);
    }

    /**
     * The frontend build stage must not run on an end-of-life Node.js release.
     */
    public function test_frontend_stage_uses_a_pinned_lts_node_release(): void
    {
        $dockerfile = file_get_contents(base_path('Dockerfile'));

        $this->assertMatchesRegularExpression('/^FROM node:(\d+)\.\d+\.\d+-alpine@sha256:[0-9a-f]{64} AS frontend$/m', $dockerfile);
        preg_match('/^FROM node:(\d+)\./m', $dockerfile, $m);
        $this->assertGreaterThanOrEqual(22, (int) $m[1], 'Node.js 20 and older are end of life');
    }

    /**
     * nginx and Redis name an exact release and digest (a floating tag such as
     * nginx:1.27-alpine stayed on a branch that no longer receives fixes), and the
     * application and edge services run with no-new-privileges. MySQL is left alone
     * here: changing its image or settings recreates the database during a deploy,
     * so it is upgraded in a separate operator step (docs/audits/2026-10-23-security-audit.md).
     */
    public function test_production_compose_images_are_pinned_and_unprivileged(): void
    {
        $compose = file_get_contents(base_path('docker-compose.prod.yml'));

        foreach (['nginx', 'redis'] as $name) {
            $this->assertMatchesRegularExpression("/^\s+image: {$name}:\d+\.\d+\.\d+[\w.-]*@sha256:[0-9a-f]{64}$/m", $compose);
        }

        preg_match('/image: nginx:(\d+\.\d+\.\d+)-/', $compose, $nginx);
        $this->assertTrue(version_compare($nginx[1], '1.30.6', '>='), "nginx {$nginx[1]} predates the 2026 security fixes");

        // x-laravel anchor (app, worker, scheduler) plus redis and web.
        $this->assertSame(3, substr_count($compose, '- no-new-privileges:true'));
    }
}
