<button {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'inline-flex min-h-[44px] items-center justify-center gap-2 rounded-lg bg-expense px-4 py-2.5 text-base font-medium text-white transition-colors duration-150 hover:bg-expense/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-expense focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
]) }}>
    {{ $slot }}
</button>
