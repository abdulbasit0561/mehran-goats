<?php

namespace App\Http\Controllers;

use App\Models\AiAnalysis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FarmAiInsightController extends Controller
{
    /** GET /ai-insights — main AI insights page. */
    public function index()
    {
        $latest = AiAnalysis::farm()->latestFirst()->first();

        $history = AiAnalysis::farm()->latestFirst()->skip(1)->take(20)->get();

        $ruleAlerts = app(\App\Services\Ai\RuleBasedAlertService::class)->alerts();

        return view('ai-insights.index', compact('latest', 'history', 'ruleAlerts'));
    }

    /** POST /ai-insights/run — queue a farm report now. */
    public function run(Request $request): JsonResponse
    {
        if (! config('ai.api_key')) {
            return response()->json(['status' => 'not_configured'], 503);
        }

        $fresh = AiAnalysis::farm()
            ->where('generated_at', '>=', now()->subMinutes(2))
            ->exists();

        if ($fresh || \Illuminate\Support\Facades\Cache::has('ai-dispatch-farm')) {
            $latest = AiAnalysis::farm()->latestFirst()->first();

            return response()->json(['status' => 'debounced'] + ($latest ? [
                'analysis' => [
                    'generated_at'   => $latest->generated_at->toIso8601String(),
                    'model'          => $latest->model,
                    'insights'       => $latest->insights ?? [],
                    'suggestions'    => $latest->suggestions ?? [],
                    'alerts'         => $latest->alerts ?? [],
                    'recommendations' => $latest->recommendations ?? [],
                ],
            ] : []));
        }

        \Illuminate\Support\Facades\Cache::put('ai-dispatch-farm', true, now()->addMinutes(2));
        \App\Jobs\RunAiAnalysis::dispatch('farm');

        return response()->json(['status' => 'started']);
    }

    /** GET /ai-insights/latest — poll for the newest farm report. */
    public function latest(): JsonResponse
    {
        $analysis = AiAnalysis::farm()->latestFirst()->first();

        if (! $analysis) {
            return response()->json(['status' => 'pending']);
        }

        return response()->json([
            'status'          => 'ready',
            'generated_at'    => $analysis->generated_at->toIso8601String(),
            'model'           => $analysis->model,
            'insights'        => $analysis->insights ?? [],
            'suggestions'     => $analysis->suggestions ?? [],
            'alerts'          => $analysis->alerts ?? [],
            'recommendations' => $analysis->recommendations ?? [],
        ]);
    }
}
