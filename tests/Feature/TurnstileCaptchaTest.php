<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Notifications\ConfirmNewsletterSubscription;
use App\Rules\Turnstile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Regression tests for audit issue #94 (CAPTCHA on public comment and newsletter forms).
 */
class TurnstileCaptchaTest extends TestCase
{
    use RefreshDatabase;

    private function enableTurnstile(bool $tokenIsValid = true): void
    {
        // Cloudflare's published test keys.
        config([
            'services.turnstile.site_key' => '1x00000000000000000000AA',
            'services.turnstile.secret_key' => '1x0000000000000000000000000000000AA',
        ]);

        Http::fake(['challenges.cloudflare.com/*' => Http::response(
            $tokenIsValid ? ['success' => true] : ['success' => false, 'error-codes' => ['invalid-input-response']]
        )]);
    }

    private function article(): Content
    {
        return Content::create([
            'title' => 'Artikel Uji',
            'slug' => 'artikel-uji',
            'category' => 'blog',
            'status' => 'published',
            'body' => '<p>Isi</p>',
        ]);
    }

    private function comment(Content $content, array $extra = [])
    {
        return $this->post(route('comments.store', $content->id), array_merge([
            'author_name' => 'Warga',
            'author_email' => 'warga@example.org',
            'body' => 'Komentar yang cukup panjang.',
        ], $extra));
    }

    public function test_forms_work_unchanged_when_turnstile_is_not_configured(): void
    {
        Notification::fake();
        $content = $this->article();

        $this->comment($content)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('comments', ['author_email' => 'warga@example.org']);

        $this->post('/newsletter/subscribe', ['email' => 'warga@example.org'])->assertSessionHasNoErrors();
        Notification::assertSentOnDemand(ConfirmNewsletterSubscription::class);

        $html = $this->get("/konten/{$content->slug}")->assertOk()->getContent();
        $this->assertStringNotContainsString('cf-turnstile', $html);
        $this->assertStringNotContainsString('challenges.cloudflare.com', $this->get('/')->headers->get('Content-Security-Policy'));
    }

    public function test_comment_without_token_is_rejected_when_enabled(): void
    {
        $this->enableTurnstile();
        $content = $this->article();

        $this->comment($content)->assertSessionHasErrors(Turnstile::FIELD);
        $this->assertDatabaseMissing('comments', ['author_email' => 'warga@example.org']);
    }

    public function test_comment_with_valid_token_is_accepted_and_verified_server_side(): void
    {
        $this->enableTurnstile();
        $content = $this->article();

        $this->comment($content, [Turnstile::FIELD => 'token-ok'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('comments', ['author_email' => 'warga@example.org']);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $request['secret'] === '1x0000000000000000000000000000000AA'
            && $request['response'] === 'token-ok');
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->enableTurnstile(tokenIsValid: false);
        $content = $this->article();

        $this->comment($content, [Turnstile::FIELD => 'token-bad'])->assertSessionHasErrors(Turnstile::FIELD);
        $this->assertDatabaseMissing('comments', ['author_email' => 'warga@example.org']);
    }

    public function test_newsletter_requires_valid_token_when_enabled(): void
    {
        Notification::fake();
        $this->enableTurnstile(tokenIsValid: false);

        $this->post('/newsletter/subscribe', ['email' => 'warga@example.org', Turnstile::FIELD => 'token-bad'])
            ->assertSessionHasErrors(Turnstile::FIELD);

        $this->assertDatabaseMissing('subscribers', ['email' => 'warga@example.org']);
        Notification::assertNothingSent();
    }

    public function test_verification_outage_fails_closed(): void
    {
        $this->enableTurnstile();
        Http::fake(fn () => throw new ConnectionException('timeout'));
        $content = $this->article();

        $this->comment($content, [Turnstile::FIELD => 'token-ok'])->assertSessionHasErrors(Turnstile::FIELD);
    }

    public function test_widget_and_csp_are_present_when_enabled(): void
    {
        $this->enableTurnstile();
        $content = $this->article();

        $response = $this->get("/konten/{$content->slug}")->assertOk();
        $html = $response->getContent();

        // Comment form, sidebar newsletter and footer newsletter each get a widget; the script loads once.
        $this->assertSame(3, substr_count($html, 'class="cf-turnstile"'));
        $this->assertSame(1, substr_count($html, 'turnstile/v0/api.js'));
        $this->assertMatchesRegularExpression('/<script nonce="[^"]+" src="https:\/\/challenges\.cloudflare\.com\/turnstile\/v0\/api\.js"/', $html);

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertMatchesRegularExpression('/script-src [^;]*https:\/\/challenges\.cloudflare\.com/', $csp);
        $this->assertMatchesRegularExpression('/frame-src [^;]*https:\/\/challenges\.cloudflare\.com/', $csp);
    }
}
