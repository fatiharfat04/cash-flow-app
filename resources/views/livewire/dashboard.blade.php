{{-- Dashboard utama (§7.5, chart §9.5) --}}
<div class="space-y-4 md:space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-ink md:text-3xl">Dashboard</h1>
        <p class="mt-1 text-sm text-ink-muted">Ringkasan keuangan kamu untuk {{ now()->translatedFormat('F Y') }}.</p>
    </div>

    {{-- Kartu ringkasan --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-card class="p-4 md:p-5">
            <p class="text-sm text-ink-muted">Saldo</p>
            <p class="mt-1 text-xl font-semibold tabular-nums {{ $this->summary['balance'] < 0 ? 'text-expense' : 'text-ink' }}">
                {{ \App\Support\Money::format($this->summary['balance']) }}
            </p>
            <p class="mt-1 text-xs text-ink-muted">Total sepanjang waktu</p>
        </x-card>

        <x-card class="p-4 md:p-5">
            <p class="text-sm text-ink-muted">Pemasukan Bulan Ini</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-income">
                {{ \App\Support\Money::format($this->summary['income_this_month']) }}
            </p>
            <p class="mt-1 text-xs text-ink-muted">Periode berjalan</p>
        </x-card>

        <x-card class="p-4 md:p-5">
            <p class="text-sm text-ink-muted">Pengeluaran Bulan Ini</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-expense">
                {{ \App\Support\Money::format($this->summary['expense_this_month']) }}
            </p>
            <p class="mt-1 text-xs text-ink-muted">Periode berjalan</p>
        </x-card>
    </div>

    {{-- Chart — data dijembatani dari Livewire dispatch ke Alpine + Chart.js (§7.5) --}}
    <section
        class="grid gap-4 lg:grid-cols-2"
        x-data="dashboardCharts({trend: @js($this->trendData), breakdown: @js($this->breakdownData)})"
        x-on:chart-data-updated.window="updateCharts($event.detail)"
    >
        <x-card>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-base font-semibold text-ink">Tren 30 Hari</h2>
                <span class="text-xs text-ink-muted">Pemasukan vs pengeluaran</span>
            </div>
            <div class="h-64 w-full">
                <canvas
                    x-ref="trendCanvas"
                    role="img"
                    aria-label="Grafik tren pemasukan dan pengeluaran 30 hari terakhir"
                ></canvas>
            </div>
            <ul class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-ink-muted">
                <li class="flex items-center gap-1.5">
                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-income" aria-hidden="true"></span>
                    Pemasukan
                </li>
                <li class="flex items-center gap-1.5">
                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-expense" aria-hidden="true"></span>
                    Pengeluaran
                </li>
            </ul>
        </x-card>

        <x-card>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-base font-semibold text-ink">Pengeluaran per Kategori</h2>
                <span class="text-xs text-ink-muted">{{ now()->translatedFormat('F Y') }}</span>
            </div>
            <div class="h-64 w-full">
                <canvas
                    x-ref="breakdownCanvas"
                    role="img"
                    aria-label="Grafik pengeluaran per kategori bulan ini"
                ></canvas>
            </div>
            @if (empty($this->breakdownData['values']))
                <p class="mt-3 text-sm text-ink-muted">Belum ada pengeluaran bulan ini.</p>
            @endif
        </x-card>
    </section>

    <div class="grid gap-4 lg:grid-cols-2">
        {{-- Transaksi terbaru --}}
        <x-card :padded="false">
            <div class="flex items-center justify-between gap-3 border-b border-cream-200 p-4 md:p-6">
                <h2 class="text-base font-semibold text-ink">Transaksi Terbaru</h2>
                <a href="{{ route('transactions.index') }}" wire:navigate class="text-sm font-medium text-cream-600 hover:text-cream-700">
                    Lihat semua
                </a>
            </div>

            <ul class="divide-y divide-cream-200">
                @forelse ($this->recentTransactions as $transaction)
                    <li class="flex items-center gap-3 p-4" wire:key="recent-{{ $transaction->id }}">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                            style="background-color: {{ $transaction->category?->color ?? '#D9BC7C' }}22; color: {{ $transaction->category?->color ?? '#6B6154' }}"
                            aria-hidden="true"
                        >
                            <x-icon :name="$transaction->category?->icon ?? 'tag'" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-base font-medium text-ink">{{ $transaction->category?->name ?? 'Tanpa kategori' }}</p>
                            <p class="truncate text-xs text-ink-muted">
                                {{ $transaction->transaction_date->translatedFormat('d M Y') }}{{ $transaction->description ? ' · '.$transaction->description : '' }}
                            </p>
                        </div>

                        <p class="shrink-0 text-base font-semibold tabular-nums {{ $transaction->type->value === 'income' ? 'text-income' : 'text-expense' }}">
                            {{ $transaction->formatted_amount }}
                        </p>
                    </li>
                @empty
                    <li class="p-4 text-sm text-ink-muted">Belum ada transaksi.</li>
                @endforelse
            </ul>
        </x-card>

        {{-- Status budget --}}
        <x-card :padded="false">
            <div class="flex items-center justify-between gap-3 border-b border-cream-200 p-4 md:p-6">
                <h2 class="text-base font-semibold text-ink">Status Budget</h2>
                <a href="{{ route('budgets.index') }}" wire:navigate class="text-sm font-medium text-cream-600 hover:text-cream-700">
                    Atur limit
                </a>
            </div>

            <ul class="divide-y divide-cream-200">
                @forelse ($this->budgetStatus as $row)
                    <li class="flex items-center justify-between gap-3 p-4" wire:key="budget-{{ $row['category']->id }}">
                        <div class="min-w-0">
                            <p class="truncate text-base font-medium text-ink">{{ $row['category']->name }}</p>
                            <p class="text-xs text-ink-muted tabular-nums">
                                {{ \App\Support\Money::format($row['spent']) }} dari {{ \App\Support\Money::format($row['limit']) }}
                            </p>
                        </div>

                        <x-badge :status="$row['status']->value">{{ $row['status']->label() }}</x-badge>
                    </li>
                @empty
                    <li class="p-4 text-sm text-ink-muted">
                        Belum ada limit budget bulan ini.
                        <a href="{{ route('budgets.index') }}" wire:navigate class="font-medium text-cream-600 hover:text-cream-700">Atur sekarang</a>
                    </li>
                @endforelse
            </ul>
        </x-card>
    </div>
</div>
