@props([
    'padded' => true,
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-cream-200 bg-white shadow-sm '.($padded ? 'p-4 md:p-6' : '')]) }}>
    {{ $slot }}
</div>
