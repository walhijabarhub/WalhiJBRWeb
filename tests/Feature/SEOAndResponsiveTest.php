<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SEOAndResponsiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed database for sitemap contents
        $this->seed(ContentSeeder::class);
    }

    /**
     * Test sitemap XML renders successfully.
     */
    public function test_sitemap_xml_loads(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->assertSee('<urlset', false)
            ->assertSee('<loc>http://localhost</loc>', false)
            ->assertSee('memadamkan-bara');
    }

    /**
     * Test robots.txt loads and contains disallow rules.
     */
    public function test_robots_txt_loads(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertStatus(200)
            ->assertSee('User-agent: *')
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /login')
            ->assertSee('Disallow: /register')
            ->assertSee('Disallow: /profile')
            ->assertSee('Sitemap:');
    }

    /**
     * Test SEO Meta tags are rendered on welcome page.
     */
    public function test_seo_meta_tags_on_welcome_page(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('<meta name="description"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('property="og:description"', false)
            ->assertSee('property="twitter:title"', false);
    }

    /**
     * Test dynamic article SEO tags, Open Graph, and JSON-LD schema on content detail page.
     */
    public function test_article_detail_has_dynamic_seo_and_open_graph(): void
    {
        $content = Content::published()->first();
        $this->assertNotNull($content);

        $response = $this->get(route('content.show', $content->slug));

        $response->assertStatus(200)
            ->assertSee('<title>'.e($content->title).' - WALHI Jawa Barat</title>', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('property="og:type" content="article"', false)
            ->assertSee('name="twitter:card" content="summary_large_image"', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('NewsArticle', false);
    }

    /**
     * Test admin routes have noindex meta tag and X-Robots-Tag response header.
     */
    public function test_admin_routes_have_noindex_and_x_robots_tag(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet')
            ->assertSee('<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">', false)
            ->assertSee('<meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet">', false);
    }

    /**
     * Test newly added publication routes render successfully.
     */
    public function test_publication_routes_render(): void
    {
        $responseKertas = $this->get(route('kertas-posisi'));
        $responseKertas->assertStatus(200)
            ->assertSee('KERTAS POSISI');

        $responseCatatan = $this->get(route('catatan-kritis'));
        $responseCatatan->assertStatus(200)
            ->assertSee('CATATAN KRITIS');
    }

    /**
     * Test donation page contains updated presets and WhatsApp direct narrative.
     */
    public function test_donation_page_contains_wa_narrative_and_presets(): void
    {
        $response = $this->get(route('donasi'));
        $response->assertStatus(200)
            ->assertSee('Ingin berdonasi untuk lingkungan hidup?')
            ->assertSee('6282119821159')
            ->assertSee('Rp 10.000')
            ->assertSee('Rp 150.000');
    }

    /**
     * Test tentang kami page contains updated vision and leadership.
     */
    public function test_tentang_kami_page_content(): void
    {
        $response = $this->get(route('about'));
        $response->assertStatus(200)
            ->assertSee('Wahyudin')
            ->assertSee('Dedy Kurniawan')
            ->assertSee('Jl. Simponi No.29')
            ->assertSee('walhijabar@gmail.com')
            ->assertDontSee('walhijabar@walhijabar.id');
    }

    /**
     * Test mobile viewport meta, sticky header, and topbar containment styling.
     */
    public function test_mobile_viewport_and_horizontal_overflow_protection(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200)
            ->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1">', false)
            ->assertSee('position: sticky', false)
            ->assertSee('position: -webkit-sticky', false)
            ->assertSee('.topbar-socials', false)
            ->assertSee('.topbar-wrapper', false);
    }

    /**
     * Test Google Search Console verification HTML endpoint and file.
     */
    public function test_google_site_verification_html_endpoint(): void
    {
        $response = $this->get('/google8abf3dd50328ee1e.html');
        $response->assertStatus(200)
            ->assertSee('google-site-verification: google8abf3dd50328ee1e.html');

        $this->assertFileExists(public_path('google8abf3dd50328ee1e.html'));
        $this->assertStringContainsString('google-site-verification: google8abf3dd50328ee1e.html', file_get_contents(public_path('google8abf3dd50328ee1e.html')));
    }

    /**
     * Test Google site verification meta tag renders on homepage.
     */
    public function test_google_site_verification_meta_tag_on_homepage(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200)
            ->assertSee('<meta name="google-site-verification" content="google8abf3dd50328ee1e">', false);
    }

    /**
     * nginx serves public/robots.txt as a static file, so its Sitemap line must name
     * the canonical www host (absolute URL, as the robots.txt standard requires).
     */
    public function test_robots_txt_points_to_canonical_www_sitemap(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString("Sitemap: https://www.walhijabar.or.id/sitemap.xml\n", $robots);
        $this->assertDoesNotMatchRegularExpression('#Sitemap: https?://walhijabar\.or\.id#', $robots);
    }

    /**
     * The HTTPS nginx template makes www the only host that serves the site: the bare
     * domain (default server) and plain HTTP redirect there with one 301, while ACME
     * challenges stay reachable on port 80 for certificate renewal.
     */
    public function test_nginx_https_redirects_bare_domain_to_www(): void
    {
        $conf = file_get_contents(base_path('docker/nginx/https/default.conf.template'));
        $servers = preg_split('/^server \{/m', $conf);
        array_shift($servers);
        $this->assertCount(3, $servers);
        [$http, $apex, $www] = $servers;

        $this->assertStringContainsString('location ^~ /.well-known/acme-challenge/', $http);
        $this->assertStringContainsString('return 301 https://www.${NGINX_SERVER_NAME}$request_uri;', $http);

        $this->assertMatchesRegularExpression('/listen 443 ssl default_server;/', $apex);
        $this->assertMatchesRegularExpression('/server_name \$\{NGINX_SERVER_NAME\};/', $apex);
        $this->assertStringContainsString('return 301 https://www.${NGINX_SERVER_NAME}$request_uri;', $apex);
        $this->assertStringContainsString('Strict-Transport-Security', $apex);
        $this->assertStringNotContainsString('laravel.conf', $apex);

        $this->assertMatchesRegularExpression('/server_name www\.\$\{NGINX_SERVER_NAME\};/', $www);
        $this->assertStringContainsString('include /etc/nginx/snippets/laravel.conf;', $www);
        $this->assertStringNotContainsString('return 301', $www);
    }
}
