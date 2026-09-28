{{-- Daftar transaksi + ringkasan + filter (§7.2, §9.4) --}}
<div class="space-y-4 md:space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink md:text-3xl">Transaksi</h1>
            <p class="mt-1 text-sm text-ink-muted">Catat dan pantau pemasukan serta pengeluaran kamu.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-button wire:click="openCreate">Tambah Transaksi</x-button>
            <x-button variant="secondary" wire:click="exportExcel">Export Excel</x-button>
        </div>
    </div>

    @if ($notice)
        <div
            @class([
                'rounded-lg px-4 py-3 text-base',
                'bg-income-light text-income' => $noticeType === 'success',
                'bg-expense-light text-expense' => $noticeType === 'error',
            ])
            role="status"
        >
            {{ $notice }}
        </div>
    @endif

    {{-- Ringkasan sesuai filter yang aktif --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-card class="p-4 md:p-5">
            <p class="text-sm text-ink-muted">Total Pemasukan</p>
            <p class="mt-1 text-lg font-semibold tabular-nums text-income">
                {{ \App\Support\Money::format($this->summary['total_income']) }}
            </p>
        </x-card>
        <x-card class="p-4 md:p-5">
            <p class="text-sm text-ink-muted">Total Pengeluaran</p>
            <p class="mt-1 text-lg font-semibold tabular-nums text-expense">
                {{ \App\Support\Money::format($this->summary['total_expense']) }}
            </p>
        </x-card>
        <x-card class="p-4 md:p-5">
            <p class="text-sm text-ink-muted">Saldo</p>
            <p class="mt-1 text-lg font-semibold tabular-nums {{ $this->summary['balance'] < 0 ? 'text-expense' : 'text-ink' }}">
                {{ \App\Support\Money::format($this->summary['balance']) }}
            </p>
        </x-card>
    </div>

    {{-- Filter --}}
    <x-card>
        <h2 class="mb-3 text-base font-semibold text-ink">Filter</h2>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <x-input-label for="period" value="Periode" />
                <select
                    wire:model="period"
                    id="period"
                    class="mt-1 w-full rounded-lg border-cream-300 px-3 py-2.5 text-base shadow-sm transition-colors duration-150 focus:border-cream-500 focus:ring-cream-500"
                >
                    <option value="today">Hari ini</option>
                    <option value="week">Minggu ini</option>
                    <option value="month">Bulan ini</option>
                    <option value="custom">Rentang tanggal</option>
                </select>
            </div>

            @if ($period === 'custom')
                <div>
                    <x-input-label for="dateFrom" value="Dari tanggal" />
                    <x-text-input wire:model="dateFrom" id="dateFrom" type="date" class="mt-1" />
                    <x-input-error :messages="$errors->get('dateFrom')" />
                </div>
                <div>
                    <x-input-label for="dateTo" value="Sampai tanggal" />
                    <x-text-input wire:model="dateTo" id="dateTo" type="date" class="mt-1" />
                    <x-input-error :messages="$errors->get('dateTo')" />
                </div>
            @endif

            <div>
                <x-input-label for="categoryFilter" value="Kategori" />
                <select
                    wire:model="categoryFilter"
                    id="categoryFilter"
                    class="mt-1 w-full rounded-lg border-cream-300 px-3 py-2.5 text-base shadow-sm transition-colors duration-150 focus:border-cream-500 focus:ring-cream-500"
                >
                    <option value="">Semua kategori</option>
                    @foreach ($this->categories as $category)
                        <option wire:key="filter-category-{{ $category->id }}" value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label for="typeFilter" value="Tipe" />
                <select
                    wire:model="typeFilter"
                    id="typeFilter"
                    class="mt-1 w-full rounded-lg border-cream-300 px-3 py-2.5 text-base shadow-sm transition-colors duration-150 focus:border-cream-500 focus:ring-cream-500"
                >
                    <option value="">Semua tipe</option>
                    <option value="income">Pemasukan</option>
                    <option value="expense">Pengeluaran</option>
                </select>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-3">
            <x-button wire:click="applyFilter">Terapkan</x-button>
            <x-button variant="secondary" wire:click="resetFilter">Reset Filter</x-button>
        </div>
    </x-card>

    {{-- Daftar transaksi --}}
    <x-card :padded="false">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-cream-200 p-4 md:p-6">
            <h2 class="text-base font-semibold text-ink">Riwayat Transaksi</h2>
            <span class="text-sm text-ink-muted">{{ $this->transactions->total() }} data</span>
        </div>

        @if ($this->transactions->count() === 0)
            <div class="p-4 md:p-6">
                <p class="text-base text-ink">Belum ada transaksi untuk filter ini.</p>
                <p class="mt-1 text-sm text-ink-muted">Tekan “Tambah Transaksi” untuk mencatat transaksi pertama kamu.</p>
            </div>
        @else
            {{-- Mobile: kartu per item (§9.4) --}}
            <ul class="divide-y divide-cream-200 md:hidden">
                @foreach ($this->transactions as $transaction)
                    <li class="p-4" wire:key="tx-mobile-{{ $transaction->id }}">
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                                style="background-color: {{ $transaction->category?->color ?? '#D9BC7C' }}22; color: {{ $transaction->category?->color ?? '#6B6154' }}"
                                aria-hidden="true"
                            >
                                <x-icon :name="$transaction->category?->icon ?? 'tag'" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-base font-medium text-ink">{{ $transaction->category?->name ?? 'Tanpa kategori' }}</p>
                                <p class="truncate text-sm text-ink-muted">{{ $transaction->description ?: '—' }}</p>
                            </div>

                            <div class="shrink-0 text-right">
                                <p
                                    class="text-base font-semibold tabular-nums {{ $transaction->type->value === 'income' ? 'text-income' : 'text-expense' }}"
                                >
                                    {{ $transaction->formatted_amount }}
                                </p>
                                <p class="mt-0.5 text-xs text-ink-muted">
                                    {{ $transaction->transaction_date->translatedFormat('d M Y') }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 flex justify-end gap-2">
                            <x-button variant="secondary" wire:click="edit({{ $transaction->id }})">
                                Ubah
                            </x-button>
                            <x-button
                                variant="danger"
                                wire:click="delete({{ $transaction->id }})"
                                wire:confirm="Hapus transaksi {{ $transaction->category?->name ?? 'ini' }} sebesar {{ $transaction->formatted_amount }}?"
                            >
                                Hapus
                            </x-button>
                        </div>
                    </li>
                @endforeach
            </ul>

            {{-- Desktop: table (§9.4) --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full text-base">
                    <thead class="bg-cream-100">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-sm font-medium text-ink-muted">Tanggal</th>
                            <th scope="col" class="px-4 py-3 text-left text-sm font-medium text-ink-muted">Kategori</th>
                            <th scope="col" class="px-4 py-3 text-left text-sm font-medium text-ink-muted">Deskripsi</th>
                            <th scope="col" class="px-4 py-3 text-left text-sm font-medium text-ink-muted">Tipe</th>
                            <th scope="col" class="px-4 py-3 text-right text-sm font-medium text-ink-muted">Jumlah</th>
                            <th scope="col" class="px-4 py-3 text-right text-sm font-medium text-ink-muted">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cream-200">
                        @foreach ($this->transactions as $transaction)
                            <tr class="transition-colors duration-150 hover:bg-cream-50" wire:key="tx-row-{{ $transaction->id }}">
                                <td class="whitespace-nowrap px-4 py-3 text-ink-muted">
                                    {{ $transaction->transaction_date->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="flex items-center gap-2">
                                        <span
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                                            style="background-color: {{ $transaction->category?->color ?? '#D9BC7C' }}22; color: {{ $transaction->category?->color ?? '#6B6154' }}"
                                            aria-hidden="true"
                                        >
                                            <x-icon :name="$transaction->category?->icon ?? 'tag'" />
                                        </span>
                                        <span class="text-ink">{{ $transaction->category?->name ?? 'Tanpa kategori' }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-ink-muted">{{ $transaction->description ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        @class([
                                            'inline-block rounded-full px-2 py-1 text-xs font-medium',
                                            'bg-income-light text-income' => $transaction->type->value === 'income',
                                            'bg-expense-light text-expense' => $transaction->type->value === 'expense',
                                        ])
                                    >
                                        {{ $transaction->type->label() }}
                                    </span>
                                </td>
                                <td
                                    class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums {{ $transaction->type->value === 'income' ? 'text-income' : 'text-expense' }}"
                                >
                                    {{ $transaction->formatted_amount }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <x-button variant="secondary" wire:click="edit({{ $transaction->id }})">
                                            Ubah
                                        </x-button>
                                        <x-button
                                            variant="danger"
                                            wire:click="delete({{ $transaction->id }})"
                                            wire:confirm="Hapus transaksi {{ $transaction->category?->name ?? 'ini' }} sebesar {{ $transaction->formatted_amount }}?"
                                        >
                                            Hapus
                                        </x-button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="p-4 md:p-6">
            {{ $this->transactions->links() }}
        </div>
    </x-card>

    <livewire:transactions.transaction-form />
</div>
