{{--
    Shared <head> assets for public pages: fonts, the Vite bundle and, when the page
    uses data-lucide icons, the bundled Lucide icons (resources/js/icons.js).
    Usage: @include('partials.head-assets') or @include('partials.head-assets', ['lucide' => true])
--}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/fonts/webfonts/font-face.css') }}">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/favicons/favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/images/favicons/favicon-16x16.png') }}">
<link rel="apple-touch-icon" href="{{ asset('assets/images/favicons/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('assets/images/favicons/site.webmanifest') }}">

@vite(['resources/css/app.css', 'resources/js/app.js'])
@if ($lucide ?? false)
@vite('resources/js/icons.js')
@endif
