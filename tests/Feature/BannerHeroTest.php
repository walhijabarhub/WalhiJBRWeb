<?php

namespace Tests\Feature;

use App\Enums\ContentCategory;
use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BannerHeroTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_fallback_hero_banners_when_database_is_empty(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('PULIHKAN JAWA BARAT');
        $response->assertSee('#Sehari Menjadi Lebih Peduli');
        $response->assertSee('Hero Banner Slider');
        $response->assertSee('SELAMATKAN HUTAN &amp; AIR', false);
    }

    public function test_hero_has_no_scroll_indicator(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Hero Banner Slider');
        $response->assertDontSee('<span>Scroll</span>', false);
    }

    public function test_homepage_renders_database_banner_when_published(): void
    {
        Content::create([
            'title' => 'BANNER KAMPANYE EKOLOGIS 2026',
            'slug' => 'banner-kampanye-ekologis-2026',
            'category' => ContentCategory::Banner->value,
            'status' => 'published',
            'body' => 'Bersama Lindungi Masa Depan Hijau Jawa Barat',
            'tags' => 'Aksi Bersama|#aksi|Gabung Relawan|/relawan',
            'image_url' => '/storage/uploads/banner-custom.jpg',
            'is_promoted' => true,
        ]);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('BANNER KAMPANYE EKOLOGIS 2026');
        $response->assertSee('Bersama Lindungi Masa Depan Hijau Jawa Barat');
        $response->assertSee('Aksi Bersama');
        $response->assertSee('#aksi');
        $response->assertSee('Gabung Relawan');
        $response->assertSee('/relawan');
    }

    public function test_homepage_does_not_render_draft_banners(): void
    {
        Content::create([
            'title' => 'BANNER RAHASIA DRAFT',
            'slug' => 'banner-rahasia-draft',
            'category' => ContentCategory::Banner->value,
            'status' => 'draft',
            'body' => 'Belum siap dipublikasikan',
            'tags' => 'Draft Btn|#draft',
        ]);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertDontSee('BANNER RAHASIA DRAFT');
        // Still renders fallback banners because no published banners exist
        $response->assertSee('PULIHKAN JAWA BARAT');
    }

    public function test_admin_can_store_and_update_banner(): void
    {
        $admin = User::factory()->admin()->create();

        // Store
        $storeResponse = $this->actingAs($admin)->post('/admin/banner', [
            'title' => 'Banner Baru Melalui Admin',
            'status' => 'published',
            'body' => 'Subjudul Banner Admin',
            'banner_btn1_text' => 'Pelajari Isu',
            'banner_btn1_url' => '#isu',
            'banner_btn2_text' => 'Bantu Kami',
            'banner_btn2_url' => '/dukung-kami/donasi-publik',
            'is_promoted' => 1,
        ]);

        $storeResponse->assertRedirect();

        $banner = Content::where('title', 'Banner Baru Melalui Admin')->first();
        $this->assertNotNull($banner);
        $this->assertEquals(ContentCategory::Banner->value, $banner->category);
        $this->assertEquals('published', $banner->status);
        $this->assertEquals('Pelajari Isu|#isu|Bantu Kami|/dukung-kami/donasi-publik', $banner->tags);

        // Update
        $updateResponse = $this->actingAs($admin)->put("/admin/banner/{$banner->id}", [
            'title' => 'Banner Terupdate Admin',
            'slug' => $banner->slug,
            'status' => 'archived',
            'body' => 'Subjudul Terupdate',
            'banner_btn1_text' => 'Isu Baru',
            'banner_btn1_url' => '#isu-baru',
            'banner_btn2_text' => 'Donasi Baru',
            'banner_btn2_url' => '/donasi-baru',
        ]);

        $updateResponse->assertRedirect();

        $banner->refresh();
        $this->assertEquals('Banner Terupdate Admin', $banner->title);
        $this->assertEquals('archived', $banner->status);
        $this->assertEquals('Isu Baru|#isu-baru|Donasi Baru|/donasi-baru', $banner->tags);
    }
}
