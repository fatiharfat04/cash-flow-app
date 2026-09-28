@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'mt-1 space-y-1 text-sm text-expense']) }}>
        @foreach ((array) $messages as $key => $message)
            <li wire:key="input-error-{{ $key }}">{{ $message }}</li>
        @endforeach
    </ul>
@endif
