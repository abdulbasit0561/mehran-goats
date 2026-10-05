<?php

namespace App\Http\Controllers;

use App\Jobs\RunAiAnalysis;
use App\Models\AiAnalysis;
use App\Models\Goat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiInsightController extends Controller
{
    /** POST goats/{goat}/ai-insights — dispatch an analysis job. */
    public function store(Request $request, Goat $goat): JsonResponse
    {
        if (! config('ai.api_key')) {
            return response()->json(['status' => 'not_configured'], 503);
        }

        // Debounce covers BOTH in-flight jobs (cache flag set at dispatch)
        // and freshly completed analyses (< 2 min old).
        $cacheKey = "ai-dispatch-{$goat->id}";
        $recent = \Illuminate\Support\Facades\Cache::has($cacheKey)
            || AiAnalysis::where('goat_id', $goat->id)
                ->where('generated_at', '>=', now()->subMinutes(2))
                ->exists();

        if ($recent) {
            $latest = AiAnalysis::where('goat_id', $goat->id)
                ->orderByDesc('generated_at')
                ->first();

            return response()->json(['status' => 'debounced'] + ($latest ? [
                'analysis' => [
                    'generated_at' => $latest->generated_at->toIso8601String(),
                    'model'        => $latest->model,
                    'insights'     => $latest->insights ?? [],
                    'suggestions'  => $latest->suggestions ?? [],
                ],
            ] : []));
        }

        \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addMinutes(2));
        RunAiAnalysis::dispatch('goat', $goat->id);

        return response()->json(['status' => 'started']);
    }

    /** GET goats/{goat}/ai-insights — history list (newest first). */
    public function index(Goat $goat): JsonResponse
    {
        $analyses = AiAnalysis::where('goat_id', $goat->id)
            ->orderByDesc('generated_at')
            ->limit(20)
            ->get()
            ->map(fn ($a) => [
                'generated_at' => $a->generated_at->toIso8601String(),
                'model'        => $a->model,
                'insights'     => $a->insights ?? [],
                'suggestions'  => $a->suggestions ?? [],
                'severity_summary' => collect($a->insights ?? [])->countBy('severity')->all(),
            ]);

        return response()->json(['analyses' => $analyses]);
    }

    /** GET goats/{goat}/ai-insights/latest — poll for the latest analysis. */
    public function latest(Goat $goat): JsonResponse
    {
        $analysis = AiAnalysis::where('goat_id', $goat->id)
            ->orderByDesc('generated_at')
            ->first();

        if (! $analysis) {
            return response()->json(['status' => 'pending']);
        }

        return response()->json([
            'status'       => 'ready',
            'generated_at' => $analysis->generated_at->toIso8601String(),
            'model'        => $analysis->model,
            'insights'     => $analysis->insights ?? [],
            'suggestions'  => $analysis->suggestions ?? [],
        ]);
    }
}
