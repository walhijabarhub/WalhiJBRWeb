<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Console\Command;

/**
 * Recovery path for an admin/editor who lost both their authenticator device and
 * recovery codes. Requires server (CLI) access, so it cannot be abused remotely.
 */
class ResetTwoFactor extends Command
{
    protected $signature = 'walhi:reset-2fa {email : Email akun yang autentikasi dua langkahnya di-reset}';

    protected $description = 'Me-reset autentikasi dua langkah (2FA) sebuah akun agar pemilik dapat mendaftar ulang';

    public function handle(): int
    {
        $user = User::where('email', strtolower(trim((string) $this->argument('email'))))->first();

        if (! $user) {
            $this->error('Akun tidak ditemukan.');

            return self::FAILURE;
        }

        if (! $this->confirm("Reset 2FA untuk {$user->email}? Pemilik akun wajib mendaftar ulang saat login berikutnya.", true)) {
            return self::FAILURE;
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        AuditLogService::log('TWO_FACTOR_RESET_CLI', 'User', $user->id, ['email' => $user->email]);

        $this->info("✅ 2FA untuk {$user->email} telah di-reset.");

        return self::SUCCESS;
    }
}
