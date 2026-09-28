<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Cash Flow — Catat pemasukan, pengeluaran &amp; budget harian</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans">
        <div class="flex min-h-screen flex-col bg-cream-300 text-ink">
            <header class="px-4 pt-6 md:px-8">
                <div class="mx-auto flex w-full max-w-5xl items-center justify-between gap-3">
                    <span class="flex items-center gap-2">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-cream-500">
                            <x-icon name="banknotes" class="h-6 w-6" />
                        </span>
                        <span class="text-xl font-semibold">Cash Flow</span>
                    </span>

                    @auth
                        <a
                            href="{{ route('dashboard') }}"
                            class="text-sm font-medium text-ink-muted underline underline-offset-4 transition-colors duration-150 hover:text-ink"
                        >
                            Buka dashboard
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="text-sm font-medium text-ink-muted underline underline-offset-4 transition-colors duration-150 hover:text-ink"
                        >
                            Masuk
                        </a>
                    @endauth
                </div>
            </header>

            <main class="flex flex-1 items-center px-4 py-12 md:px-8">
                <div class="mx-auto w-full max-w-5xl">
                    <div class="max-w-2xl">
                        <h1 class="text-2xl font-semibold md:text-3xl">
                            Arus kas jelas, keputusan lebih tenang.
                        </h1>
                        <p class="mt-3 text-base text-ink-muted">
                            Catat pemasukan dan pengeluaran dalam rupiah bulat, atur limit budget per kategori,
                            dan lihat tren keuanganmu lewat satu dashboard sederhana — nyaman dipakai dari HP
                            maupun desktop.
                        </p>

                        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                            @auth
                                <x-button href="{{ route('dashboard') }}" class="w-full sm:w-auto">
                                    Buka Dashboard
                                </x-button>
                            @else
                                <x-button href="{{ route('register') }}" class="w-full sm:w-auto">
                                    Daftar Gratis
                                </x-button>
                                <x-button href="{{ route('login') }}" variant="secondary" class="w-full sm:w-auto">
                                    Masuk
                                </x-button>
                            @endauth
                        </div>
                    </div>

                    <ul class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <li class="rounded-xl border border-cream-200 bg-white p-4 shadow-sm md:p-6">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-cream-100">
                                <x-icon name="credit-card" />
                            </span>
                            <h2 class="mt-3 text-lg font-semibold">Transaksi harian</h2>
                            <p class="mt-1 text-sm text-ink-muted">
                                Tambah, ubah, dan saring transaksi per hari, minggu, bulan, atau rentang tanggal sendiri.
                            </p>
                        </li>

                        <li class="rounded-xl border border-cream-200 bg-white p-4 shadow-sm md:p-6">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-cream-100">
                                <x-icon name="wallet" />
                            </span>
                            <h2 class="mt-3 text-lg font-semibold">Limit budget</h2>
                            <p class="mt-1 text-sm text-ink-muted">
                                Tentukan batas pengeluaran tiap kategori dan pantau statusnya: aman, waspada, atau lewat.
                            </p>
                        </li>

                        <li class="rounded-xl border border-cream-200 bg-white p-4 shadow-sm md:p-6">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-cream-100">
                                <x-icon name="chart-pie" />
                            </span>
                            <h2 class="mt-3 text-lg font-semibold">Grafik dashboard</h2>
                            <p class="mt-1 text-sm text-ink-muted">
                                Lihat tren 30 hari dan rincian pengeluaran per kategori langsung dari halaman utama.
                            </p>
                        </li>
                    </ul>
                </div>
            </main>

            <footer class="px-4 pb-8 md:px-8">
                <p class="mx-auto w-full max-w-5xl text-center text-xs text-ink-muted sm:text-left">
                    Semua nilai disimpan dalam rupiah bulat (tanpa desimal).
                </p>
            </footer>
        </div>
    </body>
</html>
