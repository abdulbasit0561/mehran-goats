@props(['compact' => false])
<div {{ $attributes->merge(['class' => 'ai-disclaimer flex items-start gap-2 text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-md '.($compact ? 'px-2.5 py-1.5' : 'px-3 py-2')]) }}>
    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
    </svg>
    <p>AI-generated advisory content. It supports farm management decisions but is not a substitute for professional veterinary diagnosis.</p>
</div>
