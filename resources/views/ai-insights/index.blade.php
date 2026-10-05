<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">AI Insights — Herd Report</h2>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner space-y-6"
             x-data="farmAi({
                latest: @js($latest ? [
                    'generated_at' => $latest->generated_at->toIso8601String(),
                    'model' => $latest->model,
                    'insights' => $latest->insights ?? [],
                    'suggestions' => $latest->suggestions ?? [],
                    'alerts' => $latest->alerts ?? [],
                    'recommendations' => $latest->recommendations ?? [],
                ] : null),
                runUrl: '{{ route('farm.ai-insights.run') }}',
                pollUrl: '{{ route('farm.ai-insights.latest') }}'
             })">

            <!-- Header row -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                        Weekly Herd Health Report
                        <span class="text-[10px] font-semibold uppercase bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded">AI</span>
                    </h3>
                    <p class="text-xs text-gray-500">Runs automatically every Monday 06:00, or trigger it now.</p>
                </div>
                <button @click="run()" :disabled="loading"
                        class="text-sm px-4 py-2 rounded-md font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                    <span x-text="loading ? 'Generating report…' : (latest ? 'Run report now' : 'Generate first report')"></span>
                </button>
            </div>

            <template x-if="error">
                <div class="flex items-start gap-2 text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">
                    <p x-text="error" class="flex-1"></p>
                    <button @click="run()" class="text-indigo-600 hover:underline text-xs whitespace-nowrap">Try again</button>
                </div>
            </template>

            <!-- Alerts strip: rule-based + AI -->
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    Alerts
                    <span class="text-[10px] font-semibold uppercase bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded">Rule-based + AI</span>
                </h4>

                <div class="space-y-2">
                    @forelse ($ruleAlerts as $ra)
                        <div class="flex items-start gap-2 border-l-2 pl-3 py-1 {{ ($ra['severity'] ?? 'warning') === 'alert' ? 'border-red-400' : 'border-amber-400' }}">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $ra['title'] }}
                                    <span class="text-[10px] font-bold uppercase {{ ($ra['severity'] ?? 'warning') === 'alert' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }} px-1.5 py-0.5 rounded ml-1">{{ ucfirst($ra['severity'] ?? 'warning') }}</span>
                                    <span class="text-[10px] font-semibold uppercase bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded ml-1">Rule-based</span>
                                </p>
                                <p class="text-xs text-gray-600 mt-0.5">{{ $ra['detail'] }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400">No rule-based alerts.</p>
                    @endforelse

                    <template x-if="latest">
                        <div>
                            <template x-for="(a, i) in latest.alerts" :key="i">
                                <div class="flex items-start gap-2 border-l-2 pl-3 py-1"
                                     :class="{ 'border-red-400': a.severity === 'alert', 'border-amber-400': a.severity === 'warning', 'border-indigo-200': a.severity === 'info' || !a.severity }">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900" x-text="a.title">
                                            <span class="text-[10px] font-bold uppercase bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded ml-1">AI</span>
                                        </p>
                                        <p class="text-xs text-gray-600 mt-0.5" x-text="a.detail"></p>
                                    </div>
                                </div>
                            </template>
                            <p x-show="!latest.alerts.length" class="text-xs text-gray-400">No AI alerts in the latest report.</p>
                        </div>
                    </template>
                </div>

                <p x-show="!latest && !ruleAlertsCount" class="text-sm text-green-700 bg-green-50 border border-green-100 rounded-md px-3 py-2 mt-2">
                    ✓ No active alerts.
                </p>
            </div>

            <!-- Report body -->
            <template x-if="loading && !latest">
                <div class="bg-white shadow-sm rounded-lg p-10 text-center text-gray-400 text-sm">Analyzing the whole herd… this usually takes under a minute.</div>
            </template>

            <template x-if="latest">
                <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6 space-y-5">
                    <div>
                        <x-ai-disclaimer />
                    </div>
                    <div class="flex items-center gap-2 text-xs text-gray-400">
                        <span x-text="'Generated ' + new Date(latest.generated_at).toLocaleString()"></span>
                        <span x-show="latest.model" x-text="'· ' + latest.model"></span>
                    </div>

                    <!-- Recommendations by area -->
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Recommendations</p>
                        <div class="grid gap-3 md:grid-cols-3">
                            @foreach (['feeding' => 'Feeding', 'breeding' => 'Breeding', 'disease_prevention' => 'Disease prevention'] as $key => $label)
                                <div class="border border-gray-100 rounded-md p-3">
                                    <p class="text-xs font-semibold text-gray-700 uppercase mb-2">{{ $label }}</p>
                                    <div class="space-y-2">
                                        <template x-for="(r, i) in latest.recommendations.filter(r => (r.area || 'disease_prevention') === '{{ $key }}')" :key="i">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900" x-text="r.title"></p>
                                                <p class="text-xs text-gray-600 mt-0.5" x-text="r.detail"></p>
                                            </div>
                                        </template>
                                        <p class="text-xs text-gray-400" x-show="!latest.recommendations.filter(r => (r.area || 'disease_prevention') === '{{ $key }}').length">None this week.</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Insights -->
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Herd insights</p>
                        <div class="space-y-2">
                            <template x-for="(ins, i) in latest.insights" :key="i">
                                <div class="border-l-2 pl-3 py-1"
                                     :class="{ 'border-red-400': ins.severity === 'alert', 'border-amber-400': ins.severity === 'warning', 'border-indigo-200': ins.severity === 'info' || !ins.severity }">
                                    <p class="text-sm font-medium text-gray-900 flex items-center gap-2">
                                        <span x-text="ins.title"></span>
                                        <span x-show="ins.severity === 'alert'" class="text-[10px] font-bold uppercase bg-red-100 text-red-700 px-1.5 py-0.5 rounded">Alert</span>
                                        <span x-show="ins.severity === 'warning'" class="text-[10px] font-bold uppercase bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded">Warning</span>
                                    </p>
                                    <p class="text-xs text-gray-600 mt-0.5" x-text="ins.detail"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Suggestions -->
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Suggestions</p>
                        <div class="space-y-2">
                            <template x-for="(s, i) in latest.suggestions" :key="i">
                                <div class="bg-green-50 border border-green-100 rounded-md px-3 py-2">
                                    <p class="text-sm font-medium text-gray-900" x-text="s.title"></p>
                                    <p class="text-xs text-gray-600 mt-0.5" x-text="s.detail"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="!loading && !latest && !error">
                <div class="bg-white shadow-sm rounded-lg p-10 text-center text-gray-400 text-sm">
                    No report yet. The first automatic report runs Monday 06:00, or click "Generate first report" above.
                </div>
            </template>

            <!-- History -->
            @if ($history->isNotEmpty())
                <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                    <h4 class="font-semibold text-gray-900 mb-3">Previous weekly reports</h4>
                    <div class="space-y-2">
                        @foreach ($history as $old)
                            <details class="border border-gray-100 rounded-md">
                                <summary class="px-3 py-2 text-sm text-gray-700 cursor-pointer flex justify-between">
                                    <span>{{ $old->generated_at->format('D, M d Y g:i A') }}</span>
                                    <span class="text-xs text-gray-400">
                                        @php $sev = collect($old->insights ?? [])->countBy('severity'); @endphp
                                        @if (($sev['alert'] ?? 0) > 0) <span class="text-red-600 font-medium">{{ $sev['alert'] }} alert</span> @endif
                                        @if (($sev['warning'] ?? 0) > 0) <span class="text-amber-600 font-medium">{{ $sev['warning'] }} warning</span> @endif
                                        {{ ($sev['info'] ?? 0) + max(0, count($old->insights ?? []) - ($sev['alert'] ?? 0) - ($sev['warning'] ?? 0)) }} info
                                    </span>
                                </summary>
                                <div class="px-3 pb-3 space-y-1.5">
                                    @foreach (($old->recommendations ?? []) as $r)
                                        <p class="text-xs"><span class="font-medium uppercase text-gray-500">{{ str_replace('_', ' ', $r['area'] ?? 'general') }}:</span> {{ $r['title'] }} — {{ $r['detail'] }}</p>
                                    @endforeach
                                    @foreach (($old->insights ?? []) as $ins)
                                        <p class="text-xs"><span class="font-medium {{ ($ins['severity'] ?? 'info') === 'alert' ? 'text-red-600' : (($ins['severity'] ?? 'info') === 'warning' ? 'text-amber-600' : 'text-gray-800') }}">{{ $ins['title'] }}</span> — {{ $ins['detail'] }}</p>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('farmAi', ({ latest, runUrl, pollUrl }) => ({
                    latest: latest,
                    loading: false,
                    error: '',
                    pollTimer: null,
                    pollSince: 0,

                    run() {
                        this.loading = true;
                        this.error = '';
                        this.pollSince = Date.now();

                        fetch(runUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                'Accept': 'application/json',
                            },
                        })
                            .then(r => {
                                if (r.status === 503) throw new Error('AI is not configured on this system. Please contact the administrator.');
                                if (!r.ok) throw new Error('AI service is temporarily unavailable. Please try again.');
                                return r.json();
                            })
                            .then(data => {
                                if (data.status === 'debounced' && data.analysis) {
                                    this.latest = data.analysis;
                                    this.loading = false;
                                    return;
                                }
                                this.startPolling();
                            })
                            .catch(e => {
                                this.loading = false;
                                this.error = (e && e.message) || 'AI service is temporarily unavailable. Please try again.';
                            });
                    },

                    startPolling() {
                        clearInterval(this.pollTimer);
                        this.pollTimer = setInterval(() => {
                            fetch(pollUrl, { headers: { 'Accept': 'application/json' } })
                                .then(r => r.ok ? r.json() : Promise.reject())
                                .then(data => {
                                    if (data.status === 'ready' && data.generated_at && new Date(data.generated_at).getTime() >= this.pollSince - 5000) {
                                        clearInterval(this.pollTimer);
                                        this.latest = data;
                                        this.loading = false;
                                    }
                                    if (Date.now() - this.pollSince > 180000) {
                                        clearInterval(this.pollTimer);
                                        this.loading = false;
                                        if (!this.latest) this.error = 'The report is taking longer than expected. Please try again.';
                                    }
                                })
                                .catch(() => {});
                        }, 3000);
                    },
                }));
            });
        </script>
    @endpush
</x-app-layout>
