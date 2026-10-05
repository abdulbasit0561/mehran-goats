<?php

namespace App\Jobs;

use App\Models\AiAnalysis;
use App\Models\Goat;
use App\Services\Ai\AiClientException;
use App\Services\Ai\AiHealthAdvisor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Runs an AI analysis in the background and stores the result.
 * scope 'goat' analyzes one goat; scope 'farm' produces the herd report.
 */
class RunAiAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public string $scope,
        public ?int $goatId = null,
    ) {}

    public function handle(AiHealthAdvisor $advisor): void
    {
        $payload = match ($this->scope) {
            'goat' => $advisor->analyzeGoat(Goat::withTrashed()->findOrFail($this->goatId)),
            'farm' => $advisor->analyzeFarm(),
            default => throw new RuntimeException("Unknown AI analysis scope [{$this->scope}]"),
        };

        AiAnalysis::create([
            'scope'           => $this->scope,
            'goat_id'         => $this->scope === 'goat' ? $this->goatId : null,
            'insights'        => $payload['insights'] ?? [],
            'suggestions'     => $payload['suggestions'] ?? [],
            'alerts'          => $payload['alerts'] ?? [],
            'recommendations' => $payload['recommendations'] ?? [],
            'model'           => config('ai.model'),
            'generated_at'    => now(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        // Friendly-message failures (AI down, bad response) are expected
        // operational noise; anything else deserves a full stack trace.
        if ($e instanceof AiClientException) {
            Log::warning('AI analysis failed: '.$e->getMessage(), [
                'scope' => $this->scope, 'goat_id' => $this->goatId,
            ]);
        } else {
            Log::error('AI analysis crashed.', [
                'scope' => $this->scope, 'goat_id' => $this->goatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
