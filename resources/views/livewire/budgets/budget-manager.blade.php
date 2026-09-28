{{-- Limit budget per kategori pengeluaran (§7.4, badge §9.4) --}}
<div class="space-y-4 md:space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-ink md:text-3xl">Budget</h1>
        <p class="mt-1 text-sm text-ink-muted">
            Tetapkan limit pengeluaran per kategori, lalu pantau pemakaiannya tiap bulan.
        </p>
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

    {{-- Navigasi bulan --}}
    <x-card>
        <div class="flex items-center justify-between gap-2">
            <x-button variant="secondary" wire:click="previousMonth">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
                <span class="sr-only sm:not-sr-only">Bulan sebelumnya</span>
            </x-button>

            <p class="text-center text-base font-semibold text-ink" aria-live="polite">
                {{ $month->translatedFormat('F Y') }}
            </p>

            <x-button variant="secondary" wire:click="nextMonth">
                <span class="sr-only sm:not-sr-only">Bulan berikutnya</span>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                </svg>
            </x-button>
        </div>
    </x-card>

    {{-- Daftar limit per kategori pengeluaran --}}
    <x-card :padded="false">
        <div class="border-b border-cream-200 p-4 md:p-6">
            <h2 class="text-base font-semibold text-ink">Limit per Kategori</h2>
            <p class="mt-1 text-sm text-ink-muted">Isi kolom nominal lalu tekan Simpan untuk menyimpan limit bulan ini.</p>
        </div>

        <ul class="divide-y divide-cream-200">
            @forelse ($this->categories as $category)
                @php($row = $this->budgetStatus->firstWhere('category.id', $category->id))
                <li class="p-4 md:p-6" x-data>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex min-w-0 items-center gap-3">
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                                style="background-color: {{ $category->color }}22; color: {{ $category->color }}"
                                aria-hidden="true"
                            >
                                <x-icon :name="$category->icon" />
                            </span>

                            <div class="min-w-0">
                                <p class="text-base font-medium text-ink">{{ $category->name }}</p>
                                @if ($row)
                                    <p class="text-sm text-ink-muted tabular-nums">
                                        Terpakai {{ \App\Support\Money::format($row['spent']) }}
                                        dari {{ \App\Support\Money::format($row['limit']) }}
                                    </p>
                                @else
                                    <p class="text-sm text-ink-muted">Belum ada limit untuk bulan ini</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <label for="limit-{{ $category->id }}" class="sr-only">Limit {{ $category->name }}</label>
                            <div class="relative min-w-0 flex-1 sm:w-48 sm:flex-none">
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-ink-muted" aria-hidden="true">Rp</span>
                                <input
                                    x-ref="limit"
                                    wire:model="limits.{{ $category->id }}"
                                    id="limit-{{ $category->id }}"
                                    type="number"
                                    min="1"
                                    step="1"
                                    inputmode="numeric"
                                    placeholder="0"
                                    class="w-full rounded-lg border-cream-300 py-2.5 pl-9 pr-3 text-base shadow-sm transition-colors duration-150 focus:border-cream-500 focus:ring-cream-500"
                                />
                            </div>
                            <x-button x-on:click="$wire.saveBudget({{ $category->id }}, Number($refs.limit.value) || 0)">
                                Simpan
                            </x-button>
                        </div>
                    </div>

                    @if ($row)
                        @php($barColor = match ($row['status']) {
                            \App\Enums\BudgetStatus::Warning => 'bg-warning',
                            \App\Enums\BudgetStatus::Exceeded => 'bg-expense',
                            default => 'bg-income',
                        })
                        <div class="mt-3">
                            <div class="h-2 w-full overflow-hidden rounded-full bg-cream-200">
                                <div
                                    class="h-full rounded-full {{ $barColor }}"
                                    style="width: {{ min((float) $row['percentage'], 100) }}%"
                                ></div>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                                <span class="text-sm text-ink-muted tabular-nums">
                                    {{ number_format($row['percentage'], 2, ',', '.') }}% terpakai
                                </span>
                                <x-badge :status="$row['status']->value">{{ $row['status']->label() }}</x-badge>
                            </div>
                        </div>
                    @endif
                </li>
            @empty
                <li class="p-4 text-base text-ink-muted md:p-6">Belum ada kategori pengeluaran.</li>
            @endforelse
        </ul>
    </x-card>
</div>
