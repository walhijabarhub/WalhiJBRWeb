<x-auth-card title="Autentikasi Dua Langkah">
    @if ($recoveryCodes)
        <p class="auth-note" style="margin-bottom: 12px;">
            Simpan kode pemulihan berikut di tempat aman (mis. pengelola kata sandi). Setiap kode hanya bisa dipakai sekali, dan daftar ini <strong>tidak akan ditampilkan lagi</strong>.
        </p>
        <ul style="font-family: monospace; font-size: 15px; background: #F4F1EA; border: 2px solid #1D1D1D; padding: 12px 16px; margin-bottom: 20px; columns: 2;">
            @foreach ($recoveryCodes as $code)
                <li>{{ $code }}</li>
            @endforeach
        </ul>
    @endif

    @if ($user->hasTwoFactorEnabled())
        <p class="auth-note" style="margin-bottom: 20px;">
            ✅ Autentikasi dua langkah <strong>aktif</strong> sejak {{ $user->two_factor_confirmed_at->translatedFormat('d F Y H:i') }}.
            Sisa kode pemulihan: {{ count($user->two_factor_recovery_codes ?? []) }}.
        </p>

        <form method="POST" action="{{ route('two-factor.recovery-codes') }}" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
            @csrf
            <label for="password-regenerate" class="auth-label">Konfirmasi Password</label>
            <input id="password-regenerate" name="password" type="password" autocomplete="current-password" required class="auth-input" />
            <button type="submit" class="auth-btn auth-btn-secondary">Buat Ulang Kode Pemulihan</button>
        </form>

        @unless ($user->requiresTwoFactor())
            <form method="POST" action="{{ route('two-factor.disable') }}" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
                @csrf
                @method('DELETE')
                <label for="password-disable" class="auth-label">Konfirmasi Password</label>
                <input id="password-disable" name="password" type="password" autocomplete="current-password" required class="auth-input" />
                <button type="submit" class="auth-btn auth-btn-danger">Nonaktifkan</button>
            </form>
        @endunless

        <a href="{{ route('admin.dashboard') }}" class="auth-btn" style="display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">Kembali ke Dashboard</a>
    @else
        @if ($user->requiresTwoFactor())
            <p class="auth-note" style="margin-bottom: 16px; font-weight: 600; color: #D95C3F;">
                Akun admin dan editor wajib mengaktifkan autentikasi dua langkah sebelum mengakses dashboard.
            </p>
        @endif

        <ol class="auth-note" style="margin: 0 0 16px 18px; list-style: decimal;">
            <li>Pasang aplikasi autentikator (Google Authenticator, Microsoft Authenticator, Aegis, 1Password, dll.).</li>
            <li>Pindai kode QR di bawah, atau masukkan kunci rahasia secara manual.</li>
            <li>Masukkan kode 6 digit yang muncul untuk mengonfirmasi.</li>
        </ol>

        <div style="display: flex; justify-content: center; margin-bottom: 12px; padding: 12px; border: 2px solid #1D1D1D; background: white;">
            {!! $qrCodeSvg !!}
        </div>
        <p class="auth-note" style="text-align: center; margin-bottom: 20px;">
            Kunci rahasia: <code style="font-size: 13px; word-break: break-all;">{{ $secret }}</code>
        </p>

        <form method="POST" action="{{ route('two-factor.confirm') }}" style="display: flex; flex-direction: column; gap: 12px;">
            @csrf
            <label for="code" class="auth-label">Kode 6 Digit</label>
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" required autofocus class="auth-input" />
            <button type="submit" class="auth-btn">Aktifkan</button>
        </form>
    @endif
</x-auth-card>
