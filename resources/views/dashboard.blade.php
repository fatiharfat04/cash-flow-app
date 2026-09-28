<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-semibold text-ink md:text-3xl">
            {{ __('Dashboard') }}
        </h1>
    </x-slot>

    <x-card>
        <p class="text-base text-ink-muted">
            {{ __("You're logged in!") }}
        </p>
    </x-card>
</x-app-layout>
