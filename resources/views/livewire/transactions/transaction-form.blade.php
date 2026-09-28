{{-- Form tambah / ubah transaksi (§7.1) --}}
{{-- class="contents" supaya wrapper ini tidak ikut terkena space-y-4 dari parent --}}
<div class="contents">
    @if ($open)
        <div
            class="fixed inset-0 z-50 flex items-end justify-center overflow-y-auto sm:items-center sm:p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="transaction-form-title"
            x-data
            x-on:keydown.escape.window="$wire.close()"
        >
            <div class="fixed inset-0 bg-ink/40" wire:click="close" aria-hidden="true"></div>

            <div class="relative w-full max-w-lg rounded-t-xl bg-white p-4 shadow-xl sm:rounded-xl sm:p-6">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h2 id="transaction-form-title" class="text-lg font-semibold text-ink">
                            {{ $transaction !== null ? 'Ubah Transaksi' : 'Tambah Transaksi' }}
                        </h2>
                        <p class="mt-1 text-sm text-ink-muted">Semua nilai memakai rupiah bulat (tanpa desimal).</p>
                    </div>
                    <button
                        type="button"
                        wire:click="close"
                        aria-label="Tutup form transaksi"
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-ink-muted transition-colors duration-150 hover:bg-cream-100 hover:text-ink"
                    >
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                @if ($notice)
                    <div
                        @class([
                            'mb-4 rounded-lg px-4 py-3 text-base',
                            'bg-income-light text-income' => $noticeType === 'success',
                            'bg-expense-light text-expense' => $noticeType === 'error',
                        ])
                        role="status"
                    >
                        {{ $notice }}
                    </div>
                @endif

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <x-input-label value="Tipe" />
                        <div class="grid grid-cols-2 gap-2" role="radiogroup" aria-label="Tipe transaksi">
                            <button
                                type="button"
                                wire:click="$set('type', 'income')"
                                role="radio"
                                aria-checked="{{ $type === 'income' ? 'true' : 'false' }}"
                                @class([
                                    'flex min-h-[44px] items-center justify-center rounded-lg border px-3 text-base font-medium transition-colors duration-150',
                                    'border-income bg-income-light text-income' => $type === 'income',
                                    'border-cream-300 bg-white text-ink-muted hover:bg-cream-50' => $type !== 'income',
                                ])
                            >
                                Pemasukan
                            </button>
                            <button
                                type="button"
                                wire:click="$set('type', 'expense')"
                                role="radio"
                                aria-checked="{{ $type === 'expense' ? 'true' : 'false' }}"
                                @class([
                                    'flex min-h-[44px] items-center justify-center rounded-lg border px-3 text-base font-medium transition-colors duration-150',
                                    'border-expense bg-expense-light text-expense' => $type === 'expense',
                                    'border-cream-300 bg-white text-ink-muted hover:bg-cream-50' => $type !== 'expense',
                                ])
                            >
                                Pengeluaran
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('type')" />
                    </div>

                    <div>
                        <x-input-label for="category_id" value="Kategori" />
                        <select
                            wire:model="category_id"
                            id="category_id"
                            class="mt-1 w-full rounded-lg border-cream-300 px-3 py-2.5 text-base shadow-sm transition-colors duration-150 focus:border-cream-500 focus:ring-cream-500"
                        >
                            <option value="">Pilih kategori</option>
                            @foreach ($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('category_id')" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="amount" value="Jumlah (Rp)" />
                            <x-text-input
                                wire:model="amount"
                                id="amount"
                                type="number"
                                min="1"
                                step="1"
                                inputmode="numeric"
                                class="mt-1"
                                placeholder="mis. 150000"
                            />
                            <x-input-error :messages="$errors->get('amount')" />
                        </div>

                        <div>
                            <x-input-label for="transaction_date" value="Tanggal" />
                            <x-text-input
                                wire:model="transaction_date"
                                id="transaction_date"
                                type="date"
                                max="{{ today()->toDateString() }}"
                                class="mt-1"
                            />
                            <x-input-error :messages="$errors->get('transaction_date')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="description" value="Deskripsi (opsional)" />
                        <x-text-input
                            wire:model="description"
                            id="description"
                            type="text"
                            maxlength="500"
                            class="mt-1"
                            placeholder="mis. Beli kopi pagi"
                            autocomplete="off"
                        />
                        <x-input-error :messages="$errors->get('description')" />
                    </div>

                    <div class="flex flex-wrap gap-3 pt-1">
                        <x-button type="submit">Simpan</x-button>
                        <x-button variant="secondary" wire:click="close">Batal</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
