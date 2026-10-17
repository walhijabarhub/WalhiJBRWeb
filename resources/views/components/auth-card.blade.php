@props(['title' => 'Keamanan Akun'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title }} - WALHI Jawa Barat</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('assets/fonts/webfonts/font-face.css') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body { font-family: 'Inter', sans-serif; background-color: #F4F1EA; }
            .auth-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #1D1D1D; letter-spacing: 0.5px; }
            .auth-input { border: 2px solid #1D1D1D; padding: 12px; font-size: 14px; outline: none; background: white; color: #1D1D1D; border-radius: 0; width: 100%; }
            .auth-input:focus { border-color: #256D4A; }
            .auth-btn { height: 52px; background: #256D4A; color: white; border: 2px solid #1D1D1D; font-weight: 700; font-size: 13px; text-transform: uppercase; cursor: pointer; letter-spacing: 0.5px; width: 100%; box-shadow: 3px 3px 0 0 #1D1D1D; transition: all 0.15s ease; }
            .auth-btn:hover { background-color: #1e5a3d; }
            .auth-btn-secondary { background: white; color: #1D1D1D; }
            .auth-btn-secondary:hover { background: #F4F1EA; }
            .auth-btn-danger { background: #D95C3F; }
            .auth-btn-danger:hover { background: #c44e32; }
            .auth-note { font-size: 13px; color: #444; line-height: 1.6; }
        </style>
    </head>
    <body class="antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-12">
            <div class="mb-8 text-center">
                <a href="/">
                    <img src="{{ asset('assets/images/resources/logo-2-walhi.png') }}" alt="WALHI Jawa Barat" style="height: 76px; object-fit: contain;" class="mx-auto drop-shadow-[2px_2px_0px_#1D1D1D]" />
                </a>
            </div>

            <div style="background: white; border: 4px solid #1D1D1D; width: 100%; max-width: 480px;" class="p-8 md:p-10 shadow-[8px_8px_0px_0px_#1D1D1D]">
                <h2 style="font-family: Aspekta, sans-serif; font-weight: 800; font-size: 24px; text-align: center; text-transform: uppercase; color: #1D1D1D; margin: 0 0 24px; line-height: 1.2;">{{ $title }}</h2>

                @if (session('status'))
                    <div style="background: #256D4A; border: 2px solid #1D1D1D; color: white; padding: 12px; font-size: 13px; font-weight: 600; margin-bottom: 20px;">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div style="background: #D95C3F; border: 2px solid #1D1D1D; color: white; padding: 12px; font-size: 13px; font-weight: 600; margin-bottom: 20px;">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                {{ $slot }}
            </div>
        </div>
    </body>
</html>
