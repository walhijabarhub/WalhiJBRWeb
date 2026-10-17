<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Seeds the primary admin, the default editor and (optionally) team members.
 *
 * Safe to run repeatedly in production:
 * - existing passwords are never overwritten unless FORCE_ADMIN_PASSWORD_RESET=true;
 * - accounts without a configured password get a unique random one and a
 *   password-reset link, so no password is shared or printed outside local dev.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $forceReset = (bool) config('auth.force_admin_password_reset', false);

        $this->seedAccount(
            email: config('auth.admin_seed.email') ?: 'admin@walhijabar.or.id',
            name: 'Admin WALHI Jabar',
            role: 'admin',
            configuredPassword: config('auth.admin_seed.password'),
            forceReset: $forceReset,
        );

        $this->seedAccount(
            email: config('auth.editor_seed.email') ?: 'editor@walhijabar.or.id',
            name: 'Editor WALHI Jabar',
            role: 'editor',
            configuredPassword: config('auth.editor_seed.password'),
            forceReset: $forceReset,
        );

        // Team members from a local, git-ignored JSON file. Each member gets an
        // individual random password and must set their own via the reset link.
        $localJsonPath = database_path('seeders/admins.local.json');
        if (! file_exists($localJsonPath)) {
            $localJsonPath = database_path('seeders/admins.json');
        }

        if (! file_exists($localJsonPath)) {
            $this->command?->line('ℹ️  File admins.local.json tidak ditemukan, melewati seeding tim tambahan.');

            return;
        }

        $teamAdmins = json_decode((string) file_get_contents($localJsonPath), true);
        if (! is_array($teamAdmins)) {
            $this->command?->warn('⚠️  File daftar tim tidak valid (bukan array JSON).');

            return;
        }

        foreach ($teamAdmins as $member) {
            if (empty($member['email']) || ! filter_var($member['email'], FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $role = in_array($member['role'] ?? 'editor', ['admin', 'editor'], true) ? $member['role'] ?? 'editor' : 'editor';

            $this->seedAccount(
                email: Str::lower($member['email']),
                name: $member['name'] ?? explode('@', $member['email'])[0],
                role: $role,
                configuredPassword: null,
                forceReset: $forceReset,
            );
        }
    }

    private function seedAccount(string $email, string $name, string $role, ?string $configuredPassword, bool $forceReset): void
    {
        $user = User::firstOrNew(['email' => $email]);
        $isNew = ! $user->exists;
        $needsPassword = $isNew || empty($user->password) || $forceReset;
        $generated = false;

        if ($needsPassword) {
            if (filled($configuredPassword)) {
                $user->password = Hash::make($configuredPassword);
            } else {
                $user->password = Hash::make(Str::password(32));
                $generated = true;
            }
        }

        if ($isNew) {
            $user->name = $name;
        }
        $user->email_verified_at = $user->email_verified_at ?: now();
        $user->assignRole($role);
        $user->save();

        if ($generated) {
            // The random password is never shown; the owner sets their own via email.
            rescue(fn () => Password::sendResetLink(['email' => $user->email]), report: false);
        }

        if ($this->command) {
            $state = $isNew ? 'dibuat' : ($needsPassword ? 'diperbarui (password di-reset)' : 'sudah ada, password tidak diubah');
            $this->command->info("Akun {$role} {$user->email} {$state}.");

            if ($generated) {
                $this->command->warn("   Password acak dibuat dan tautan reset dikirim ke {$user->email}. Jika email belum dikonfigurasi, jalankan: php artisan walhi:create-admin");
            }
        }
    }
}
