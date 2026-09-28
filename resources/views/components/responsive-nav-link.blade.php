@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-cream-500 text-start text-base font-medium text-cream-700 bg-cream-100 focus:outline-none focus:text-cream-700 focus:bg-cream-200 focus:border-cream-600 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-ink-muted hover:text-ink hover:bg-cream-50 hover:border-cream-300 focus:outline-none focus:text-ink focus:bg-cream-50 focus:border-cream-300 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
