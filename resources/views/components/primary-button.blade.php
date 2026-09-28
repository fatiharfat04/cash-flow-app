<button {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'inline-flex min-h-[44px] items-center justify-center gap-2 rounded-lg bg-cream-500 px-4 py-2.5 text-base font-medium text-ink transition-colors duration-150 hover:bg-cream-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-cream-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
]) }}>
    {{ $slot }}
</button>
