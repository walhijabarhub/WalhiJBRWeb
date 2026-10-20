<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every public page renders on an empty database (replaces the Breeze ExampleTest,
 * which only checked the home page).
 */
class PublicPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public static function publicPages(): array
    {
        return [
            'beranda' => ['/'],
            'blog' => ['/blog'],
            'tentang kami' => ['/tentang-kami'],
            'kontak' => ['/kontak'],
            'pengaduan' => ['/pengaduan'],
            'kebijakan privasi' => ['/kebijakan-privasi'],
            'transparansi dana' => ['/transparansi-dana'],
            'regulasi' => ['/regulasi'],
            'siaran pers' => ['/publikasi/siaran-pers'],
            'infografis' => ['/publikasi/infografis'],
            'laporan tahunan' => ['/publikasi/laporan-tahunan'],
            'kertas posisi' => ['/publikasi/kertas-posisi'],
            'catatan kritis' => ['/publikasi/catatan-kritis'],
            'donasi' => ['/dukung-kami/donasi-publik'],
            'sitemap' => ['/sitemap.xml'],
            'robots' => ['/robots.txt'],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_public_page_renders(string $uri): void
    {
        $this->get($uri)->assertOk();
    }

    public function test_favicon_is_not_empty(): void
    {
        $this->assertGreaterThan(0, filesize(public_path('favicon.ico')));
    }
}
