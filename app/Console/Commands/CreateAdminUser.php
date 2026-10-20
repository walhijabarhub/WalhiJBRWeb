<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'walhi:create-admin
                            {--name= : Nama lengkap admin}
                            {--email= : Alamat email atau username}
                            {--role=admin : Peran user (admin atau editor)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membuat akun administrator atau editor baru secara aman via CLI/VPS';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('───────────────────────────────────────────────────────');
        $this->info('  🌿 WALHI Jawa Barat — Pembuatan Akun Administrator');
        $this->info('───────────────────────────────────────────────────────');

        $name = $this->option('name') ?: $this->ask('Nama Lengkap');
        while (empty($name)) {
            $this->error('Nama wajib diisi.');
            $name = $this->ask('Nama Lengkap');
        }

        $emailInput = $this->option('email') ?: $this->ask('Email atau Username (misal: nama atau nama@walhijabar.or.id)');
        while (empty($emailInput)) {
            $this->error('Email atau Username wajib diisi.');
            $emailInput = $this->ask('Email atau Username');
        }

        $email = trim($emailInput);
        if (! str_contains($email, '@')) {
            $email = Str::lower($email).'@walhijabar.or.id';
        }

        $role = $this->option('role');
        if (! in_array($role, ['admin', 'editor'], true)) {
            $role = $this->choice('Pilih Peran Akun', ['admin', 'editor'], 0);
        }

        $password = null;
        while (! $password) {
            $pwd = $this->secret('Password (min. 12 karakter, huruf besar/kecil, angka, simbol)');
            $pwdConfirmation = $this->secret('Ulangi Password');

            $validator = Validator::make(
                [
                    'password' => $pwd,
                    'password_confirmation' => $pwdConfirmation,
                ],
                [
                    'password' => ['required', 'string', 'confirmed', PasswordRule::defaults()],
                ],
                [
                    'password.required' => 'Password wajib diisi.',
                    'password.confirmed' => 'Konfirmasi password tidak cocok.',
                ]
            );

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $this->error('  • '.$error);
                }
                $this->newLine();

                continue;
            }

            $password = $pwd;
        }

        $user = User::firstOrNew(['email' => $email]);
        $isNew = ! $user->exists;

        $user->name = $name;
        $user->password = Hash::make($password);
        $user->email_verified_at = $user->email_verified_at ?: now();
        $user->assignRole($role === 'editor' ? UserRole::Editor : UserRole::Admin);
        $user->save();

        $actionText = $isNew ? 'berhasil dibuat' : 'berhasil diperbarui';
        $this->newLine();
        $this->info("✅ Akun {$role} {$name} ({$email}) {$actionText}!");
        $this->line('   Login URL : '.route('login'));
        $this->line('   Username  : '.explode('@', $email)[0]);
        $this->line("   Email     : {$email}");
        $this->newLine();

        return self::SUCCESS;
    }
}
