<x-auth-card title="Verifikasi Dua Langkah">
    <p class="auth-note" style="margin-bottom: 20px;">
        Masukkan kode 6 digit dari aplikasi autentikator Anda. Jika perangkat tidak tersedia, gunakan salah satu kode pemulihan.
    </p>

    <form method="POST" action="{{ route('two-factor.challenge.store') }}" style="display: flex; flex-direction: column; gap: 18px;">
        @csrf

        <div style="display: flex; flex-direction: column; gap: 6px;">
            <label for="code" class="auth-label">Kode Autentikator</label>
            <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9 ]*" autocomplete="one-time-code" autofocus class="auth-input" />
        </div>

        <div style="display: flex; flex-direction: column; gap: 6px;">
            <label for="recovery_code" class="auth-label">Atau Kode Pemulihan</label>
            <input id="recovery_code" name="recovery_code" type="text" autocomplete="off" class="auth-input" />
        </div>

        <button type="submit" class="auth-btn">Verifikasi &amp; Masuk</button>
    </form>
</x-auth-card>
