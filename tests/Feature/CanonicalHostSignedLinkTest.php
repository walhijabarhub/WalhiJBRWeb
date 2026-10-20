<?php

namespace Tests\Feature;

use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * nginx redirects https://walhijabar.or.id to https://www.walhijabar.or.id, while the
 * newsletter confirmation e-mail is built in the queue worker from APP_URL. A signed
 * link must therefore keep working when it is opened on a different host than the one
 * it was built for, without weakening the signature check.
 */
class CanonicalHostSignedLinkTest extends TestCase
{
    use RefreshDatabase;

    private const APEX = 'https://walhijabar.or.id';

    private const WWW = 'https://www.walhijabar.or.id';

    private Subscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => self::APEX]);
        $this->subscriber = Subscriber::create(['email' => 'warga@example.org', 'is_active' => false]);
    }

    protected function tearDown(): void
    {
        URL::forceRootUrl(null);
        URL::forceScheme(null);

        parent::tearDown();
    }

    /** Build a URL the way the queue worker does: no request, root = APP_URL. */
    private function asWorker(callable $build): string
    {
        URL::forceRootUrl(self::APEX);
        URL::forceScheme('https');
        $url = $build();
        URL::forceRootUrl(null);
        URL::forceScheme(null);

        return $url;
    }

    private function onWww(string $url): string
    {
        return str_replace(self::APEX, self::WWW, $url);
    }

    public function test_new_confirmation_link_works_after_redirect_to_www(): void
    {
        $link = $this->asWorker(fn () => $this->subscriber->confirmationUrl());
        $this->assertStringStartsWith(self::APEX.'/newsletter/konfirmasi/', $link);

        $this->get($this->onWww($link))->assertRedirect(route('home'));

        $this->assertTrue($this->subscriber->fresh()->is_active);
    }

    public function test_new_confirmation_link_also_works_on_the_bare_domain(): void
    {
        $link = $this->asWorker(fn () => $this->subscriber->confirmationUrl());

        $this->get($link)->assertRedirect(route('home'));
    }

    public function test_link_emailed_before_the_change_still_works_on_www(): void
    {
        // Old links were signed over the absolute APP_URL (bare domain).
        $link = $this->asWorker(fn () => URL::temporarySignedRoute('newsletter.confirm', now()->addDays(7), ['subscriber' => $this->subscriber->id]));

        $this->get($this->onWww($link))->assertRedirect(route('home'));

        $this->assertTrue($this->subscriber->fresh()->is_active);
    }

    public function test_unsigned_tampered_and_expired_links_are_rejected(): void
    {
        $this->get(self::WWW.'/newsletter/konfirmasi/'.$this->subscriber->id)->assertForbidden();

        $other = Subscriber::create(['email' => 'lain@example.org', 'is_active' => false]);
        $link = $this->onWww($this->asWorker(fn () => $this->subscriber->confirmationUrl()));
        $this->get(str_replace('/konfirmasi/'.$this->subscriber->id.'?', '/konfirmasi/'.$other->id.'?', $link))->assertForbidden();
        $this->get(preg_replace('/expires=\d+/', 'expires='.now()->addYear()->getTimestamp(), $link))->assertForbidden();

        $expired = $this->asWorker(fn () => URL::temporarySignedRoute('newsletter.confirm', now()->subMinute(), ['subscriber' => $this->subscriber->id], absolute: false));
        $this->get(self::WWW.$expired)->assertForbidden();
        $expiredAbsolute = $this->asWorker(fn () => URL::temporarySignedRoute('newsletter.confirm', now()->subMinute(), ['subscriber' => $this->subscriber->id]));
        $this->get($this->onWww($expiredAbsolute))->assertForbidden();

        $this->assertFalse($this->subscriber->fresh()->is_active);
        $this->assertFalse($other->fresh()->is_active);
    }
}
