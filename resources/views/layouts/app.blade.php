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

        <!-- Chart.js — sesuai §2: CDN, JANGAN npm install -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans">
        <div class="min-h-screen bg-cream-300">
            <livewire:layout.navigation />

            <main class="md:pl-64">
                <div class="mx-auto w-full max-w-5xl px-4 pb-28 pt-20 md:px-8 md:pb-12 md:pt-8">
                    @if (isset($header))
                        <div class="mb-5">
                            {{ $header }}
                        </div>
                    @endif

                    {{ $slot }}
                </div>
            </main>

            <x-nav-bottom />
        </div>
    </body>
</html>
