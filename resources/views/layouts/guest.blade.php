<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' — '.config('app.name', 'Cash Flow') : config('app.name', 'Cash Flow') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans">
        <div class="flex min-h-screen flex-col items-center justify-center bg-cream-300 px-4 py-8">
            <a href="{{ route('dashboard') }}" class="mb-6 flex items-center gap-2">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-cream-500 text-ink">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v7.5m0 0v7.5m0-7.5h7.5m-7.5 0H3m12.75 0v7.5m0 0v7.5m0-7.5h7.5m-7.5 0H15m-2.25-7.5V6m0 0V6m0 0h.008v.008H15V6zm2.25 0h.008v.008H17.25V6zm0 4.5h.008v.008H17.25v-.008zm0 4.5h.008v.008H17.25v-.008zM15 4.5V6m0 4.5v.008H15V10.5zm2.25 0v.008h.008V10.5H17.25z" />
                    </svg>
                </span>
                <span class="text-xl font-semibold text-ink">Cash Flow</span>
            </a>

            <div class="w-full max-w-md rounded-xl border border-cream-200 bg-white p-6 shadow-sm sm:p-8">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
