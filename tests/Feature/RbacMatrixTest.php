<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacMatrixTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $editor;

    private User $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin_test@walhijabar.or.id',
            'role' => UserRole::Admin->value,
        ]);

        $this->editor = User::factory()->create([
            'email' => 'editor_test@walhijabar.or.id',
            'role' => UserRole::Editor->value,
        ]);

        $this->subscriber = User::factory()->create([
            'email' => 'subscriber_test@walhijabar.or.id',
            'role' => UserRole::Subscriber->value,
        ]);
    }

    // ── 1. Admin RBAC Permissions ─────────────────────────────────────

    public function test_admin_can_create_normal_content(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/blog', [
            'title' => 'Admin Normal Blog',
            'status' => 'published',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('contents', [
            'title' => 'Admin Normal Blog',
            'category' => 'blog',
        ]);
    }

    public function test_admin_can_create_sensitive_content(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/kontak', [
            'title' => 'Admin Sensitive Kontak',
            'status' => 'published',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('contents', [
            'title' => 'Admin Sensitive Kontak',
            'category' => 'kontak',
        ]);
    }

    public function test_admin_can_update_sensitive_content(): void
    {
        $content = Content::create([
            'title' => 'Initial Kontak',
            'slug' => 'initial-kontak',
            'category' => 'kontak',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->admin)->put('/admin/kontak/'.$content->id, [
            'title' => 'Updated Kontak by Admin',
            'slug' => 'initial-kontak',
            'status' => 'published',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('contents', [
            'id' => $content->id,
            'title' => 'Updated Kontak by Admin',
        ]);
    }

    public function test_admin_can_delete_content(): void
    {
        $content = Content::create([
            'title' => 'To Delete',
            'slug' => 'to-delete',
            'category' => 'blog',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->admin)->delete('/admin/blog/'.$content->id);
        $response->assertSessionHasNoErrors();
        $this->assertSoftDeleted('contents', ['id' => $content->id]);
    }

    // ── 2. Editor RBAC Permissions & Restrictions ─────────────────────

    public function test_editor_can_create_normal_content(): void
    {
        $response = $this->actingAs($this->editor)->post('/admin/blog', [
            'title' => 'Editor Normal Blog',
            'status' => 'published',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('contents', [
            'title' => 'Editor Normal Blog',
            'category' => 'blog',
        ]);
    }

    public function test_editor_cannot_create_sensitive_content(): void
    {
        $response = $this->actingAs($this->editor)->post('/admin/kontak', [
            'title' => 'Editor Sensitive Kontak Attempt',
            'status' => 'published',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('contents', [
            'title' => 'Editor Sensitive Kontak Attempt',
        ]);
    }

    public function test_editor_can_update_normal_content(): void
    {
        $content = Content::create([
            'title' => 'Initial Blog',
            'slug' => 'initial-blog',
            'category' => 'blog',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->editor)->put('/admin/blog/'.$content->id, [
            'title' => 'Updated Blog by Editor',
            'slug' => 'initial-blog',
            'status' => 'published',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('contents', [
            'id' => $content->id,
            'title' => 'Updated Blog by Editor',
        ]);
    }

    public function test_editor_cannot_update_sensitive_content(): void
    {
        $content = Content::create([
            'title' => 'Initial Sensitive Kontak',
            'slug' => 'initial-sensitive-kontak',
            'category' => 'kontak',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->editor)->put('/admin/kontak/'.$content->id, [
            'title' => 'Malicious Edit by Editor',
            'slug' => 'initial-sensitive-kontak',
            'status' => 'published',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('contents', [
            'title' => 'Malicious Edit by Editor',
        ]);
    }

    public function test_editor_cannot_delete_content(): void
    {
        $content = Content::create([
            'title' => 'Editor Delete Target',
            'slug' => 'editor-delete-target',
            'category' => 'blog',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->editor)->delete('/admin/blog/'.$content->id);
        $response->assertStatus(403);
        $this->assertDatabaseHas('contents', [
            'id' => $content->id,
            'deleted_at' => null,
        ]);
    }

    // ── 3. Subscriber Restrictions ────────────────────────────────────

    public function test_subscriber_cannot_access_or_modify_admin_content(): void
    {
        $responseIndex = $this->actingAs($this->subscriber)->get('/admin/blog');
        $responseIndex->assertStatus(403);

        $responseCreate = $this->actingAs($this->subscriber)->post('/admin/blog', [
            'title' => 'Subscriber Attack',
            'status' => 'published',
        ]);
        $responseCreate->assertStatus(403);
    }

    // ── 4. Category Whitelist Rejection ───────────────────────────────

    public function test_arbitrary_or_unknown_category_returns_404(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/unknown-arbitrary-category');
        $response->assertStatus(404);

        $responsePost = $this->actingAs($this->admin)->post('/admin/unknown-arbitrary-category', [
            'title' => 'Arbitrary Category Attempt',
            'status' => 'published',
        ]);
        $responsePost->assertStatus(404);
    }
}
