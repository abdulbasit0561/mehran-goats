@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-stone-200 focus:border-emerald-600 focus:ring-emerald-600/30 rounded-xl shadow-sm']) }}>
