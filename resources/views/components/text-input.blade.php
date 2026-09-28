@props(['disabled' => false])

<input
    @disabled($disabled)
    {{ $attributes->merge([
        'class' => 'w-full rounded-lg border-cream-300 px-3 py-2.5 text-base shadow-sm transition-colors duration-150 focus:border-cream-500 focus:ring-cream-500',
    ]) }}
>
