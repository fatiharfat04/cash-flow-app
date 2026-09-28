@php
    $items = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Transaksi', 'route' => 'transactions.index', 'active' => 'transactions.*', 'icon' => 'banknotes'],
        ['label' => 'Kategori', 'route' => 'categories.index', 'active' => 'categories.*', 'icon' => 'tag'],
        ['label' => 'Budget', 'route' => 'budgets.index', 'active' => 'budgets.*', 'icon' => 'chart-pie'],
        ['label' => 'Profil', 'route' => 'profile', 'active' => 'profile', 'icon' => 'user'],
    ];

    $icons = [
        'home' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
        'banknotes' => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v7.5m0 0v7.5m0-7.5h7.5m-7.5 0H3m12.75 0v7.5m0 0v7.5m0-7.5h7.5m-7.5 0H15m-2.25-7.5V6m0 0V6m0 0h.008v.008H15V6zm2.25 0h.008v.008H17.25V6zm0 4.5h.008v.008H17.25v-.008zm0 4.5h.008v.008H17.25v-.008zM15 4.5V6m0 4.5v.008H15V10.5zm2.25 0v.008h.008V10.5H17.25z',
        'tag' => 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3zM6 6h.008v.008H6V6z',
        'chart-pie' => 'M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6zM13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z',
        'user' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
    ];
@endphp

{{-- Bottom navigation — hanya mobile, disembunyikan di desktop (md:hidden) --}}
<nav
    class="safe-area-bottom fixed bottom-0 left-0 right-0 z-40 grid grid-cols-5 border-t border-cream-200 bg-white md:hidden"
    aria-label="Navigasi bawah"
>
    @foreach ($items as $item)
        @if (Route::has($item['route']))
            <a
                href="{{ route($item['route']) }}"
                wire:navigate
                wire:key="nav-bottom-{{ $item['route'] }}"
                @class([
                    'flex h-16 flex-col items-center justify-center gap-0.5 text-xs transition-colors duration-150',
                    'text-cream-600 font-medium' => request()->routeIs($item['active']),
                    'text-ink-muted' => ! request()->routeIs($item['active']),
                ])
                @if (request()->routeIs($item['active'])) aria-current="page" @endif
            >
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$item['icon']] }}" />
                </svg>
                <span>{{ $item['label'] }}</span>
            </a>
        @endif
    @endforeach
</nav>
