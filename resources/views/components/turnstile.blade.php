@props(['size' => 'normal'])

{{-- Cloudflare Turnstile widget; renders nothing unless TURNSTILE_SITE_KEY/SECRET are set. --}}
@if (\App\Rules\Turnstile::enabled())
    @once
        <script nonce="{{ Vite::cspNonce() }}" src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endonce
    <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-language="id" data-size="{{ $size }}"></div>
    @error(\App\Rules\Turnstile::FIELD)
        <div style="color: #D95C3F; font-size: 12px; font-weight: 600;">{{ $message }}</div>
    @enderror
@endif
