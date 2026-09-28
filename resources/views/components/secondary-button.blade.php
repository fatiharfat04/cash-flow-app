<button {{ $attributes->merge([
    'type' => 'button',
    'class' => 'inline-flex min-h-[44px] items-center justify-center gap-2 rounded-lg border border-cream-400 bg-white px-4 py-2.5 text-base font-medium text-ink transition-colors duration-150 hover:bg-cream-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-cream-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
]) }}>
    {{ $slot }}
</button>
