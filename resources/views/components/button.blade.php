@props([
    'variant' => 'primary',
    'type' => 'button',
    'href' => null,
    'disabled' => false,
    'block' => false,
])

@php
    $baseClasses = 'inline-flex min-h-[44px] items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-base font-medium transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-cream-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50';

    $variantClasses = [
        'primary' => 'bg-cream-500 text-ink hover:bg-cream-600',
        'secondary' => 'bg-white border border-cream-400 text-ink hover:bg-cream-50',
        'danger' => 'bg-expense text-white hover:bg-expense/90',
        'ghost' => 'text-ink-muted hover:bg-cream-100',
    ];

    $classes = $baseClasses.' '.($variantClasses[$variant] ?? $variantClasses['primary']).($block ? ' w-full' : '');
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        {{ $attributes->merge(['class' => $classes]) }}
        @if ($disabled) aria-disabled="true" tabindex="-1" @endif
    >{{ $slot }}</a>
@else
    <button
        type="{{ $type }}"
        {{ $attributes->merge(['class' => $classes]) }}
        @if ($disabled) disabled @endif
    >{{ $slot }}</button>
@endif
