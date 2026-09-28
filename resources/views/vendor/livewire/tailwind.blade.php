{{--
    Override view pagination bawaan Livewire (`livewire::tailwind`).
    Dipublish ke resources/views/vendor/livewire/ supaya warna mengikuti
    color tokens §9.1 (cream/ink) dan teks memakai bahasa Indonesia.
--}}
@php
    if (! isset($scrollTo)) {
        $scrollTo = 'body';
    }

    $scrollIntoViewJsSnippet = ($scrollTo !== false)
        ? <<<JS
           (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
        JS
        : '';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex justify-between gap-3 sm:hidden">
                @if ($paginator->onFirstPage())
                    <span
                        class="inline-flex min-h-[44px] flex-1 items-center justify-center rounded-lg border border-cream-300 bg-white px-4 py-2.5 text-base font-medium text-ink-muted"
                        aria-disabled="true"
                    >
                        Sebelumnya
                    </span>
                @else
                    <button
                        type="button"
                        wire:click="previousPage('{{ $paginator->getPageName() }}')"
                        x-on:click="{{ $scrollIntoViewJsSnippet }}"
                        wire:loading.attr="disabled"
                        class="inline-flex min-h-[44px] flex-1 items-center justify-center rounded-lg border border-cream-400 bg-white px-4 py-2.5 text-base font-medium text-ink transition-colors duration-150 hover:bg-cream-50"
                    >
                        Sebelumnya
                    </button>
                @endif

                @if ($paginator->hasMorePages())
                    <button
                        type="button"
                        wire:click="nextPage('{{ $paginator->getPageName() }}')"
                        x-on:click="{{ $scrollIntoViewJsSnippet }}"
                        wire:loading.attr="disabled"
                        class="inline-flex min-h-[44px] flex-1 items-center justify-center rounded-lg border border-cream-400 bg-white px-4 py-2.5 text-base font-medium text-ink transition-colors duration-150 hover:bg-cream-50"
                    >
                        Berikutnya
                    </button>
                @else
                    <span
                        class="inline-flex min-h-[44px] flex-1 items-center justify-center rounded-lg border border-cream-300 bg-white px-4 py-2.5 text-base font-medium text-ink-muted"
                        aria-disabled="true"
                    >
                        Berikutnya
                    </span>
                @endif
            </div>

            <p class="text-sm text-ink-muted">
                Menampilkan
                <span class="font-medium text-ink">{{ $paginator->firstItem() }}</span>
                sampai
                <span class="font-medium text-ink">{{ $paginator->lastItem() }}</span>
                dari
                <span class="font-medium text-ink">{{ $paginator->total() }}</span>
                data
            </p>

            <div class="hidden items-center gap-2 sm:flex">
                <button
                    type="button"
                    wire:click="previousPage('{{ $paginator->getPageName() }}')"
                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                    wire:loading.attr="disabled"
                    aria-label="Halaman sebelumnya"
                    @disabled($paginator->onFirstPage())
                    class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-cream-400 bg-white text-ink transition-colors duration-150 hover:bg-cream-50 disabled:cursor-not-allowed disabled:border-cream-300 disabled:text-ink-muted"
                >
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </button>

                <div class="flex items-center gap-1">
                    @foreach ($paginator->getUrlRange(max($paginator->currentPage() - 2, 1), min($paginator->currentPage() + 2, $paginator->lastPage())) as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span
                                aria-current="page"
                                wire:key="pagination-page-{{ $page }}"
                                class="inline-flex h-11 min-w-[44px] items-center justify-center rounded-lg bg-cream-500 px-3 text-base font-semibold text-ink"
                            >
                                {{ $page }}
                            </span>
                        @else
                            <button
                                type="button"
                                wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                wire:key="pagination-page-{{ $page }}"
                                aria-label="Halaman {{ $page }}"
                                class="inline-flex h-11 min-w-[44px] items-center justify-center rounded-lg border border-cream-400 bg-white px-3 text-base font-medium text-ink transition-colors duration-150 hover:bg-cream-50"
                            >
                                {{ $page }}
                            </button>
                        @endif
                    @endforeach
                </div>

                <button
                    type="button"
                    wire:click="nextPage('{{ $paginator->getPageName() }}')"
                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                    wire:loading.attr="disabled"
                    aria-label="Halaman berikutnya"
                    @disabled(! $paginator->hasMorePages())
                    class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-cream-400 bg-white text-ink transition-colors duration-150 hover:bg-cream-50 disabled:cursor-not-allowed disabled:border-cream-300 disabled:text-ink-muted"
                >
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </button>
            </div>
        </nav>
    @endif
</div>
