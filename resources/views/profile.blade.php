<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-semibold text-ink md:text-3xl">
            {{ __('Profile') }}
        </h1>
    </x-slot>

    <div class="space-y-4 md:space-y-6">
        <x-card>
            <livewire:profile.update-profile-information-form />
        </x-card>

        <x-card>
            <livewire:profile.update-password-form />
        </x-card>

        <x-card>
            <livewire:profile.delete-user-form />
        </x-card>
    </div>
</x-app-layout>
