@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full bg-zinc-950/80 border border-zinc-800 focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-lg px-3.5 py-2.5 text-xs text-zinc-100 placeholder:text-zinc-500 focus:outline-none transition-colors']) }}>
