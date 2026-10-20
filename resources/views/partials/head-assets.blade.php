{{--
    Shared <head> assets for public pages: fonts, the Vite bundle and, when the page
    uses data-lucide icons, the Lucide script.
    Usage: @include('partials.head-assets') or @include('partials.head-assets', ['lucide' => true])
--}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/fonts/webfonts/font-face.css') }}">

@vite(['resources/css/app.css', 'resources/js/app.js'])
@if ($lucide ?? false)

<script nonce="{{ Vite::cspNonce() }}" src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js" integrity="sha384-ieG+IKD0d/ZPXyCBTMVAbqsQdns8QGJR/e26WMw7M4fkaI/rHcS/YIoi+ah9WGge" crossorigin="anonymous"></script>
@endif
