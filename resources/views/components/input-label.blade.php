@props(['value'])

<label {{ $attributes->merge(['class' => 'mb-1 block text-sm font-medium text-ink-muted']) }}>
    {{ $value ?? $slot }}
</label>
