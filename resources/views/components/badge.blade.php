@props([
    'status' => 'safe',
])

@php
    $statusClasses = [
        'safe' => 'bg-income-light text-income',
        'warning' => 'bg-warning/10 text-warning',
        'exceeded' => 'bg-expense-light text-expense',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-block rounded-full px-2 py-1 text-xs font-medium '.($statusClasses[$status] ?? $statusClasses['safe'])]) }}>
    {{ $slot }}
</span>
