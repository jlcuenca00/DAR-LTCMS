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
        <div class="flex min-h-screen min-w-0 items-center justify-center bg-gradient-to-br from-green-950 via-green-900 to-slate-950 px-3 py-6 sm:px-4 sm:py-8">
            <div class="w-full max-w-md min-w-0">
                <div class="mb-5 px-2 text-center text-white sm:mb-6">
                    <a href="/" class="mx-auto mb-4 grid h-16 w-16 place-items-center rounded-2xl border border-white/20 bg-white shadow-xl sm:h-20 sm:w-20">
                        <x-application-logo class="h-11 w-11 fill-current text-green-800 sm:h-14 sm:w-14" />
                    </a>
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-green-100 sm:text-xs sm:tracking-[0.24em]">DAR Negros Oriental</p>
                    <h1 class="mt-2 text-xl font-black tracking-tight sm:text-2xl">DAR-LTCMS</h1>
                    <p class="mt-1 text-xs font-semibold leading-relaxed text-green-100 sm:text-sm">Land Transfer Clearance and Monitoring System</p>
                </div>

                <div class="w-full min-w-0 rounded-2xl border border-white/15 bg-white p-4 shadow-2xl sm:p-7">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
