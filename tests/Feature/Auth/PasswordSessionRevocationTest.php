<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\TwoFactorService;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PasswordSessionRevocationTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_PASSWORD = 'Walhi2026#SecurePassword!9x';

    private string $sessionDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionDirectory = storage_path('framework/testing/password-sessions-'.bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($this->sessionDirectory);
        config([
            'session.driver' => 'file',
            'session.files' => $this->sessionDirectory,
            'session.lottery' => [0, 100],
            'auth.two_factor.enforced' => false,
        ]);
        $this->mock(UncompromisedVerifier::class)->shouldReceive('verify')->andReturn(true);
    }

    protected function tearDown(): void
    {
        if (isset($this->sessionDirectory)) {
            File::deleteDirectory($this->sessionDirectory);
        }

        parent::tearDown();
    }

    public static function passwordActions(): array
    {
        return [['reset'], ['change']];
    }

    public function test_reset_revokes_both_active_devices_even_before_their_first_authenticated_request(): void
    {
        $user = User::factory()->create();
        $first = $second = [];
        $this->login($first, $user);
        $this->login($second, $user);

        $this->replacePassword('reset', $first, $user);

        $this->requestAs($first, 'GET', '/confirm-password')->assertRedirect(route('login'));
        $this->requestAs($second, 'GET', '/profile')->assertRedirect(route('login'));

        $fresh = [];
        $this->login($fresh, $user, self::NEW_PASSWORD);
        $this->requestAs($fresh, 'GET', '/confirm-password')->assertOk();
    }

    public function test_change_revokes_other_active_device_but_keeps_and_rotates_current_session(): void
    {
        $user = User::factory()->create();
        $current = $other = [];
        $this->login($current, $user);
        $this->login($other, $user);
        $this->requestAs($other, 'GET', '/profile')->assertOk();
        $this->requestAs($current, 'GET', '/confirm-password')->assertOk();
        $oldId = app('session.store')->getId();

        $this->replacePassword('change', $current, $user);

        $this->assertNotSame($oldId, app('session.store')->getId());
        $this->requestAs($current, 'GET', '/profile')->assertOk();
        $this->requestAs($other, 'GET', '/confirm-password')->assertRedirect(route('login'));
        $this->requestAs($current, 'GET', '/confirm-password')->assertOk();
    }

    #[DataProvider('passwordActions')]
    public function test_legacy_sessions_without_password_hash_cannot_adopt_changed_password(string $action): void
    {
        $user = User::factory()->create();
        $current = $legacy = [];
        $this->login($current, $user);
        $this->login($legacy, $user);
        $store = app('session.store');
        $store->forget('password_hash_web');
        $store->save();

        $this->replacePassword($action, $current, $user);

        $this->requestAs($legacy, 'GET', '/confirm-password')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    #[DataProvider('passwordActions')]
    public function test_late_save_of_concurrent_old_session_does_not_restore_access(string $action): void
    {
        $user = User::factory()->create();
        $current = $other = [];
        $this->login($current, $user);
        $this->login($other, $user);
        $this->requestAs($other, 'GET', '/confirm-password')->assertOk();
        $path = $this->sessionDirectory.'/'.app('session.store')->getId();
        $inFlightPayload = File::get($path);

        $this->replacePassword($action, $current, $user);
        // An overlapping request can still persist its pre-change session after the change.
        File::put($path, $inFlightPayload);

        $this->requestAs($other, 'GET', '/profile')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    #[DataProvider('passwordActions')]
    public function test_old_remember_cookie_cannot_restore_login_after_password_replacement(string $action): void
    {
        $user = User::factory()->create();
        $current = $remembered = [];
        $this->login($current, $user);
        $this->login($remembered, $user, remember: true);
        $recaller = Auth::guard('web')->getRecallerName();
        $this->assertArrayHasKey($recaller, $remembered);
        $stolenRememberCookie = [$recaller => $remembered[$recaller]];
        $oldToken = $user->fresh()->getRememberToken();

        $this->replacePassword($action, $current, $user);

        $this->assertNotSame($oldToken, $user->fresh()->getRememberToken());
        $this->requestAs($stolenRememberCookie, 'GET', '/profile')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_valid_remember_cookie_can_restore_a_session(): void
    {
        $user = User::factory()->create();
        $browser = [];
        $this->login($browser, $user, remember: true);
        unset($browser[config('session.cookie')]);

        $this->requestAs($browser, 'GET', '/confirm-password')->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->requestAs($browser, 'GET', '/profile')->assertOk();
    }

    #[DataProvider('passwordActions')]
    public function test_pending_two_factor_login_cannot_finish_after_password_replacement(string $action): void
    {
        $user = User::factory()->create();
        $current = $pending = [];
        $this->login($current, $user);
        $this->enableTwoFactor($user);
        $this->requestAs($pending, 'POST', route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();

        $this->replacePassword($action, $current, $user);

        $this->requestAs($pending, 'POST', '/two-factor-challenge', [
            'recovery_code' => 'aaaaa-bbbbb',
        ])->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertSame(['aaaaa-bbbbb'], $user->fresh()->two_factor_recovery_codes);
    }

    public function test_completed_two_factor_login_seeds_the_password_hash(): void
    {
        $user = User::factory()->create();
        $this->enableTwoFactor($user);
        $browser = [];
        $this->requestAs($browser, 'POST', route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));
        $this->requestAs($browser, 'POST', '/two-factor-challenge', [
            'recovery_code' => 'aaaaa-bbbbb',
        ])->assertRedirect('/admin');

        $this->requestAs($browser, 'GET', '/confirm-password')->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_failed_password_change_does_not_revoke_either_device(): void
    {
        $user = User::factory()->create();
        $current = $other = [];
        $this->login($current, $user);
        $this->login($other, $user);

        $this->requestAs($current, 'PUT', '/password', [
            'current_password' => 'wrong-password',
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->requestAs($current, 'GET', '/confirm-password')->assertOk();
        $this->requestAs($other, 'GET', '/confirm-password')->assertOk();
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_guest_public_and_password_pages_are_unchanged(): void
    {
        $guest = [];
        $this->requestAs($guest, 'GET', '/')->assertOk();
        $this->requestAs($guest, 'GET', route('login'))->assertOk();
        $this->requestAs($guest, 'GET', '/forgot-password')->assertOk();
        $this->requestAs($guest, 'GET', '/reset-password/example-token')->assertOk();
        $this->requestAs($guest, 'GET', '/profile')->assertRedirect(route('login'));
    }

    private function enableTwoFactor(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => app(TwoFactorService::class)->generateSecret(),
            'two_factor_recovery_codes' => ['aaaaa-bbbbb'],
            'two_factor_confirmed_at' => now(),
        ])->save();
    }

    private function replacePassword(string $action, array &$current, User $user): void
    {
        if ($action === 'reset') {
            $guest = [];
            $this->requestAs($guest, 'POST', '/reset-password', [
                'token' => Password::createToken($user),
                'email' => $user->email,
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ])->assertSessionHasNoErrors()->assertRedirect(route('login'));
        } else {
            $this->requestAs($current, 'PUT', '/password', [
                'current_password' => 'password',
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ])->assertSessionHasNoErrors()->assertSessionHas('status', 'password-updated');
        }

        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $user->fresh()->password));
    }

    private function login(array &$cookies, User $user, string $password = 'password', bool $remember = false): void
    {
        $this->requestAs($cookies, 'POST', route('login'), [
            'email' => $user->email,
            'password' => $password,
            'remember' => $remember,
        ])->assertSessionHasNoErrors()->assertRedirect('/admin');
    }

    /**
     * Make each request with only this browser's encrypted cookies, a new guard and
     * a newly loaded file session, rather than leaking in-memory auth between clients.
     */
    private function requestAs(array &$cookies, string $method, string $uri, array $data = []): TestResponse
    {
        Auth::forgetGuards();
        app('session')->forgetDrivers();
        $this->app->forgetInstance('session.store');
        // The redirector is a singleton that otherwise keeps the preceding client's store.
        app('redirect')->setSession(app('session.store'));
        foreach (app('cookie')->getQueuedCookies() as $cookie) {
            app('cookie')->unqueue($cookie->getName(), $cookie->getPath());
        }

        $response = $this->call($method, $uri, $data, $cookies);

        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getExpiresTime() !== 0 && $cookie->getExpiresTime() < time()) {
                unset($cookies[$cookie->getName()]);
            } else {
                $cookies[$cookie->getName()] = $cookie->getValue();
            }
        }

        return $response;
    }
}
