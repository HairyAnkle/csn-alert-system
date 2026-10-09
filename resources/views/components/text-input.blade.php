@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'min-h-[44px] w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 shadow-sm transition focus:border-brand-700 focus:ring-2 focus:ring-brand-700/20 focus:outline-none disabled:bg-slate-50 disabled:text-slate-400']) }}>
