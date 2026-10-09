<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-700 focus:ring-offset-2 disabled:opacity-50']) }} style="background-color: #005B96;">
    {{ $slot }}
</button>
