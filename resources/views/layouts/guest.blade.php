<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'DAR-LTCMS') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
        <link rel="shortcut icon" type="image/png" href="{{ asset('images/favicon.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/favicon.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen bg-gradient-to-br from-green-950 via-green-900 to-slate-950 px-4 py-8 flex items-center justify-center">
            <div class="w-full max-w-md">
                <div class="mb-6 text-center text-white">
                    <a href="/" class="mx-auto mb-4 grid h-20 w-20 place-items-center rounded-2xl border border-white/20 bg-white shadow-xl">
                        <x-application-logo class="h-14 w-14 fill-current text-green-800" />
                    </a>
                    <p class="text-xs font-black uppercase tracking-[0.24em] text-green-100">DAR Negros Oriental</p>
                    <h1 class="mt-2 text-2xl font-black tracking-tight">DAR-LTCMS</h1>
                    <p class="mt-1 text-sm font-semibold text-green-100">Land Transfer Clearance and Monitoring System</p>
                </div>

                <div class="w-full rounded-2xl border border-white/15 bg-white p-7 shadow-2xl">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
