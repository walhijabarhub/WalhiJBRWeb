<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Content\ImageOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageOptimizerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function dimensions(string $path): array
    {
        return array_slice(getimagesize(Storage::disk('public')->path($path)), 0, 2);
    }

    public function test_large_jpeg_is_scaled_to_max_dimension_keeping_aspect_ratio(): void
    {
        $path = UploadedFile::fake()->image('foto.jpg', 4000, 3000)->store('uploads', 'public');

        ImageOptimizer::shrink($path);

        $this->assertSame([1920, 1440], $this->dimensions($path));
    }

    public function test_large_png_keeps_format_and_transparency(): void
    {
        $path = UploadedFile::fake()->image('logo.png', 3000, 1000)->store('uploads', 'public');

        ImageOptimizer::shrink($path);

        $info = getimagesize(Storage::disk('public')->path($path));
        $this->assertSame([1920, 640, IMAGETYPE_PNG], [$info[0], $info[1], $info[2]]);
    }

    public function test_small_images_and_gifs_are_left_untouched(): void
    {
        $small = UploadedFile::fake()->image('kecil.jpg', 800, 600)->store('uploads', 'public');
        $gif = UploadedFile::fake()->image('animasi.gif', 3000, 2000)->store('uploads', 'public');
        $before = [md5(Storage::disk('public')->get($small)), md5(Storage::disk('public')->get($gif))];

        ImageOptimizer::shrink($small);
        ImageOptimizer::shrink($gif);

        $this->assertSame($before, [md5(Storage::disk('public')->get($small)), md5(Storage::disk('public')->get($gif))]);
    }

    public function test_images_above_the_pixel_budget_are_left_untouched(): void
    {
        $path = UploadedFile::fake()->image('panorama.jpg', 8000, 2500)->store('uploads', 'public');
        $before = md5(Storage::disk('public')->get($path));

        ImageOptimizer::shrink($path);

        $this->assertSame($before, md5(Storage::disk('public')->get($path)));
    }

    public function test_unreadable_file_is_ignored(): void
    {
        Storage::disk('public')->put('uploads/rusak.jpg', 'bukan gambar');

        ImageOptimizer::shrink('uploads/rusak.jpg');

        $this->assertSame('bukan gambar', Storage::disk('public')->get('uploads/rusak.jpg'));
    }

    public function test_editor_upload_is_scaled(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $url = $this->postJson(route('admin.upload-image'), [
            'image' => UploadedFile::fake()->image('quill.jpg', 2500, 2500)->size(1500),
        ])->assertOk()->json('url');

        $this->assertSame([1920, 1920], $this->dimensions(str_replace('/storage/', '', $url)));
    }

    public function test_content_cover_upload_is_scaled(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.content.store', ['category' => 'blog']), [
            'title' => 'Artikel Bergambar',
            'status' => 'published',
            'image' => UploadedFile::fake()->image('sampul.jpg', 3840, 2160)->size(1800),
        ])->assertSessionHasNoErrors();

        $files = Storage::disk('public')->files('uploads');
        $this->assertCount(1, $files);
        $this->assertSame([1920, 1080], $this->dimensions($files[0]));
    }
}
