<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>Berhenti Berlangganan - WALHI Jawa Barat</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('assets/fonts/webfonts/font-face.css') }}">
        @vite(['resources/css/app.css'])
    </head>
    <body class="antialiased bg-brand-cream" style="font-family: Montserrat, sans-serif;">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-12">
            <a href="{{ route('home') }}" class="mb-8">
                <img src="{{ asset('assets/images/resources/logo-2-walhi.png') }}" alt="WALHI Jawa Barat" style="height: 76px; object-fit: contain;" />
            </a>
            <div class="bg-white border-4 border-[#1D1D1D] shadow-[8px_8px_0px_0px_#1D1D1D] p-8 w-full max-w-md text-center">
                <h1 class="text-xl font-extrabold uppercase text-[#1D1D1D] mb-4">Berhenti Berlangganan Nawala</h1>

                @if ($done)
                    <p class="text-sm text-[#444] mb-6">Alamat <strong>{{ $subscriber->email }}</strong> tidak akan lagi menerima Nawala WALHI Jawa Barat.</p>
                    <a href="{{ route('home') }}" class="inline-block bg-[#256D4A] text-white font-bold uppercase text-sm px-6 py-3 border-2 border-[#1D1D1D]">Kembali ke Beranda</a>
                @else
                    <p class="text-sm text-[#444] mb-6">Hentikan pengiriman Nawala ke <strong>{{ $subscriber->email }}</strong>?</p>
                    <form method="POST" action="{{ route('newsletter.unsubscribe.store', ['token' => $subscriber->token]) }}">
                        @csrf
                        <button type="submit" class="w-full bg-[#D95C3F] text-white font-bold uppercase text-sm px-6 py-3 border-2 border-[#1D1D1D]">Ya, Berhenti Berlangganan</button>
                    </form>
                @endif
            </div>
        </div>
    </body>
</html>
