<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Auth\TwoFactorService;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Regression tests for audit issues #70 (login matching & throttling), #71 (2FA),
 * #74 (admin seeder) and #94 (reset-link enumeration, last admin deletion).
 */
class AuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function login(string $login, string $password = 'password')
    {
        return $this->post(route('login'), ['email' => $login, 'password' => $password]);
    }

    private function enableTwoFactor(User $user): string
    {
        $secret = app(TwoFactorService::class)->generateSecret();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => ['aaaaa-bbbbb', 'ccccc-ddddd'],
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $secret;
    }

    private function currentCode(string $secret): string
    {
        return app(Google2FA::class)->getCurrentOtp($secret);
    }

    // ── #70 Login matching & throttling ────────────────────────────────

    public function test_wildcard_and_partial_logins_do_not_match_any_account(): void
    {
        User::factory()->create(['email' => 'admin@walhijabar.or.id', 'role' => UserRole::Admin->value]);

        foreach (['%', '%%', 'a%', '_dmin', 'adm%', '%@walhijabar.or.id'] as $login) {
            $this->login($login);
            $this->assertGuest();
        }
    }

    public function test_username_login_matches_exact_local_part_only(): void
    {
        $user = User::factory()->create(['email' => 'teammember@walhijabar.co.id']);

        $this->login('TeamMember');
        $this->assertAuthenticatedAs($user);
    }

    public function test_throttle_is_per_account_regardless_of_login_spelling(): void
    {
        User::factory()->create(['email' => 'teammember@walhijabar.or.id']);

        // Five failures using different spellings of the same account …
        foreach (['teammember', 'TEAMMEMBER', 'teammember@walhijabar.or.id', ' teammember ', 'TeamMember'] as $login) {
            $this->login($login, 'wrong');
        }

        // … lock the account even with the correct password.
        $response = $this->login('teammember');
        $this->assertGuest();
        $response->assertSessionHasErrors('email');
        $this->assertNotSame(trans('auth.failed'), session('errors')->first('email'));
    }

    // ── #71 Two-factor authentication ──────────────────────────────────

    public function test_admin_without_two_factor_is_sent_to_enrolment_when_enforced(): void
    {
        config(['auth.two_factor.enforced' => true]);
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $this->actingAs($admin)->get('/admin')->assertRedirect(route('two-factor.show'));
        $this->actingAs($admin)->get(route('two-factor.show'))->assertOk()->assertSee('<svg', false);
    }

    public function test_profile_is_also_behind_two_factor_enrolment(): void
    {
        config(['auth.two_factor.enforced' => true]);
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $this->actingAs($admin)->get('/profile')->assertRedirect(route('two-factor.show'));
        $this->actingAs($admin)->delete('/profile', ['password' => 'password'])->assertRedirect(route('two-factor.show'));
        $this->assertModelExists($admin);
    }

    public function test_successful_login_rehashes_outdated_password(): void
    {
        $user = User::factory()->create([
            'email' => 'lama@walhijabar.or.id',
            'password' => Hash::make('password', ['rounds' => 4]),
        ]);
        config(['hashing.bcrypt.rounds' => 5]);

        $this->login('lama@walhijabar.or.id');

        $this->assertAuthenticatedAs($user);
        $this->assertFalse(Hash::needsRehash($user->fresh()->password));
    }

    public function test_password_reset_mail_failure_does_not_change_response(): void
    {
        User::factory()->create(['email' => 'ada@walhijabar.or.id']);
        Notification::shouldReceive('send')->andThrow(new \RuntimeException('SMTP down'));

        $this->post('/forgot-password', ['email' => 'ada@walhijabar.or.id'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');
    }

    public function test_enrolment_requires_a_valid_code_and_shows_recovery_codes_once(): void
    {
        config(['auth.two_factor.enforced' => true]);
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $this->actingAs($admin)->get(route('two-factor.show'))->assertOk();
        $secret = session('two_factor.pending_secret');
        $this->assertNotEmpty($secret);

        $this->actingAs($admin)->post(route('two-factor.confirm'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertFalse($admin->fresh()->hasTwoFactorEnabled());

        $this->actingAs($admin)->post(route('two-factor.confirm'), ['code' => $this->currentCode($secret)])
            ->assertRedirect(route('two-factor.show'));
        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());

        $codes = $admin->fresh()->two_factor_recovery_codes;
        $this->actingAs($admin)->get(route('two-factor.show'))->assertSee($codes[0]);
        $this->actingAs($admin)->get(route('two-factor.show'))->assertDontSee($codes[0]);

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_secret_is_stored_encrypted(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);
        $secret = $this->enableTwoFactor($admin);

        $raw = \DB::table('users')->where('id', $admin->id)->value('two_factor_secret');
        $this->assertNotSame($secret, $raw);
        $this->assertSame($secret, $admin->fresh()->two_factor_secret);
    }

    public function test_login_with_two_factor_requires_second_step(): void
    {
        $admin = User::factory()->create(['email' => 'ketua@walhijabar.or.id', 'role' => UserRole::Admin->value]);
        $secret = $this->enableTwoFactor($admin);

        $this->login('ketua@walhijabar.or.id')->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();

        $this->post(route('two-factor.challenge.store'), ['code' => '123456'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post(route('two-factor.challenge.store'), ['code' => $this->currentCode($secret)])
            ->assertRedirect(route('admin.dashboard', absolute: false));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_totp_code_cannot_be_replayed(): void
    {
        $admin = User::factory()->create(['email' => 'ketua@walhijabar.or.id', 'role' => UserRole::Admin->value]);
        $secret = $this->enableTwoFactor($admin);
        $code = $this->currentCode($secret);

        $this->login('ketua@walhijabar.or.id');
        $this->post(route('two-factor.challenge.store'), ['code' => $code]);
        $this->assertAuthenticatedAs($admin);

        $this->post('/logout');
        $this->login('ketua@walhijabar.or.id');
        $this->post(route('two-factor.challenge.store'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_recovery_code_logs_in_once(): void
    {
        $admin = User::factory()->create(['email' => 'ketua@walhijabar.or.id', 'role' => UserRole::Admin->value]);
        $this->enableTwoFactor($admin);

        $this->login('ketua@walhijabar.or.id');
        $this->post(route('two-factor.challenge.store'), ['recovery_code' => 'AAAAA-BBBBB']);
        $this->assertAuthenticatedAs($admin);
        $this->assertSame(['ccccc-ddddd'], $admin->fresh()->two_factor_recovery_codes);

        $this->post('/logout');
        $this->login('ketua@walhijabar.or.id');
        $this->post(route('two-factor.challenge.store'), ['recovery_code' => 'aaaaa-bbbbb'])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_challenge_without_pending_login_redirects_to_login(): void
    {
        $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
        $this->post(route('two-factor.challenge.store'), ['code' => '123456'])->assertRedirect(route('login'));
    }

    public function test_admin_cannot_disable_enforced_two_factor(): void
    {
        config(['auth.two_factor.enforced' => true]);
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);
        $this->enableTwoFactor($admin);

        $this->actingAs($admin)->delete(route('two-factor.disable'), ['password' => 'password'])
            ->assertSessionHasErrors('password');
        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());
    }

    public function test_cli_can_reset_two_factor(): void
    {
        $admin = User::factory()->create(['email' => 'ketua@walhijabar.or.id', 'role' => UserRole::Admin->value]);
        $this->enableTwoFactor($admin);

        $this->artisan('walhi:reset-2fa', ['email' => 'ketua@walhijabar.or.id'])
            ->expectsConfirmation("Reset 2FA untuk {$admin->email}? Pemilik akun wajib mendaftar ulang saat login berikutnya.", 'yes')
            ->assertSuccessful();

        $this->assertFalse($admin->fresh()->hasTwoFactorEnabled());
    }

    // ── #74 Admin seeder ───────────────────────────────────────────────

    public function test_seeder_does_not_overwrite_existing_passwords(): void
    {
        config(['auth.admin_seed.email' => 'admin@walhijabar.or.id', 'auth.admin_seed.password' => 'Seeder-Pass-123!']);
        $admin = User::factory()->create([
            'email' => 'admin@walhijabar.or.id',
            'password' => Hash::make('Original-Pass-123!'),
            'role' => UserRole::Admin->value,
        ]);

        $this->seed(AdminUserSeeder::class);

        $this->assertTrue(Hash::check('Original-Pass-123!', $admin->fresh()->password));
    }

    public function test_seeder_overwrites_only_when_forced(): void
    {
        config([
            'auth.admin_seed.email' => 'admin@walhijabar.or.id',
            'auth.admin_seed.password' => 'Seeder-Pass-123!',
            'auth.force_admin_password_reset' => true,
        ]);
        $admin = User::factory()->create(['email' => 'admin@walhijabar.or.id', 'role' => UserRole::Admin->value]);

        $this->seed(AdminUserSeeder::class);

        $this->assertTrue(Hash::check('Seeder-Pass-123!', $admin->fresh()->password));
    }

    public function test_seeder_without_password_generates_random_one_and_sends_reset_link(): void
    {
        Notification::fake();
        config(['auth.admin_seed.email' => 'baru@walhijabar.or.id', 'auth.admin_seed.password' => null]);

        $this->seed(AdminUserSeeder::class);

        $admin = User::where('email', 'baru@walhijabar.or.id')->firstOrFail();
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse(Hash::check('', $admin->password));
        Notification::assertSentTo($admin, ResetPassword::class);
    }

    // ── #94 Account enumeration & last admin ───────────────────────────

    public function test_password_reset_response_is_identical_for_known_unknown_and_throttled(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'ada@walhijabar.or.id']);

        $known = $this->post('/forgot-password', ['email' => 'ada@walhijabar.or.id']);
        $throttled = $this->post('/forgot-password', ['email' => 'ada@walhijabar.or.id']);
        $unknown = $this->post('/forgot-password', ['email' => 'tidakada@walhijabar.or.id']);

        foreach ([$known, $throttled, $unknown] as $response) {
            $response->assertSessionHasNoErrors();
            $response->assertSessionHas('status');
        }
    }

    public function test_last_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $this->actingAs($admin)->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'password');
        $this->assertModelExists($admin);

        User::factory()->create(['role' => UserRole::Admin->value]);
        $this->actingAs($admin)->delete('/profile', ['password' => 'password'])->assertRedirect('/');
        $this->assertModelMissing($admin);
    }
}
