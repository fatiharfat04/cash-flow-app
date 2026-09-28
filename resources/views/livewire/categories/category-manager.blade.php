<div class="space-y-4 md:space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-ink md:text-3xl">Kategori</h1>
        <p class="mt-1 text-sm text-ink-muted">Atur kategori pemasukan dan pengeluaran kamu.</p>
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

    {{-- Form tambah / ubah --}}
    <x-card>
        <h2 class="mb-4 text-lg font-semibold text-ink">
            {{ $editingId ? 'Ubah Kategori' : 'Tambah Kategori' }}
        </h2>

        <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-input-label for="name" value="Nama kategori" />
                <x-text-input wire:model="name" id="name" type="text" class="mt-1" placeholder="mis. Kopi" autocomplete="off" />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label value="Tipe" />
                <div class="mt-1 grid grid-cols-2 gap-2" role="radiogroup" aria-label="Tipe kategori">
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
                <x-input-label for="icon" value="Icon" />
                <div class="mt-1 flex items-center gap-2">
                    <span
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-cream-300"
                        style="background-color: {{ $color }}22; color: {{ $color }}"
                        aria-hidden="true"
                    >
                        <x-icon :name="$icon" />
                    </span>
                    <select
                        wire:model="icon"
                        id="icon"
                        class="w-full rounded-lg border-cream-300 px-3 py-2.5 text-base shadow-sm focus:border-cream-500 focus:ring-cream-500"
                    >
                        @foreach ($this->availableIcons as $availableIcon)
                            <option value="{{ $availableIcon }}">{{ $availableIcon }}</option>
                        @endforeach
                    </select>
                </div>
                <x-input-error :messages="$errors->get('icon')" />
            </div>

            <div>
                <x-input-label for="color" value="Warna" />
                <div class="mt-1 flex items-center gap-3">
                    <input
                        type="color"
                        wire:model="color"
                        id="color"
                        aria-label="Pilih warna kategori"
                        class="h-11 w-16 cursor-pointer rounded-lg border border-cream-300 bg-white p-1"
                    />
                    <span class="text-base text-ink-muted" x-data="{{ json_encode(['color' => $color]) }}" x-text="color" x-on:color-updated.window="color = $event.detail"></span>
                </div>
                <x-input-error :messages="$errors->get('color')" />
            </div>

            <div class="flex flex-wrap gap-3 sm:col-span-2">
                <x-button type="submit">
                    {{ $editingId ? 'Simpan Perubahan' : 'Tambah Kategori' }}
                </x-button>
                @if ($editingId)
                    <x-button variant="secondary" wire:click="resetForm">Batal</x-button>
                @endif
            </div>
        </form>
    </x-card>

    {{-- Daftar kategori --}}
    <x-card>
        <h2 class="mb-4 text-lg font-semibold text-ink">Daftar Kategori</h2>

        @forelse ($this->categories as $typeKey => $groups)
            <div class="mb-5 last:mb-0">
                <h3 class="mb-2 text-sm font-medium text-ink-muted">
                    {{ $typeKey === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                </h3>

                <ul class="divide-y divide-cream-200">
                    @foreach ($groups as $category)
                        <li class="flex items-center gap-3 py-3">
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                                style="background-color: {{ $category->color }}22; color: {{ $category->color }}"
                                aria-hidden="true"
                            >
                                <x-icon :name="$category->icon" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-base font-medium text-ink">{{ $category->name }}</p>
                                <p class="text-xs text-ink-muted">{{ $category->isDefault() ? 'Kategori bawaan' : 'Kategori saya' }}</p>
                            </div>

                            @unless ($category->isDefault())
                                <div class="flex shrink-0 items-center gap-1">
                                    <button
                                        type="button"
                                        wire:click="edit({{ $category->id }})"
                                        aria-label="Ubah kategori {{ $category->name }}"
                                        class="flex h-10 w-10 items-center justify-center rounded-full text-ink-muted transition-colors duration-150 hover:bg-cream-100 hover:text-ink"
                                    >
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="delete({{ $category->id }})"
                                        wire:confirm="Hapus kategori {{ $category->name }}?"
                                        aria-label="Hapus kategori {{ $category->name }}"
                                        class="flex h-10 w-10 items-center justify-center rounded-full text-ink-muted transition-colors duration-150 hover:bg-expense-light hover:text-expense"
                                    >
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            @endunless
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <p class="text-base text-ink-muted">Belum ada kategori.</p>
        @endforelse
    </x-card>
</div>
