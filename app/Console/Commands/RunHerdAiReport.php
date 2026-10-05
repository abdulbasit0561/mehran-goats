<?php

namespace App\Console\Commands;

use App\Jobs\RunAiAnalysis;
use App\Models\AiAnalysis;
use Illuminate\Console\Command;

class RunHerdAiReport extends Command
{
    protected $signature = 'ai:herd-report {--fresh : Run synchronously instead of queueing}';

    protected $description = 'Generate the weekly herd AI health report (feeding, breeding, disease prevention)';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $job = new RunAiAnalysis('farm');
            $job->handle(app(\App\Services\Ai\AiHealthAdvisor::class));

            $this->info('Herd report generated and stored.');
        } else {
            RunAiAnalysis::dispatch('farm');
            $this->info('Herd report generation queued.');
        }

        // Retention: keep the latest 52 farm reports, prune older ones
        $staleIds = AiAnalysis::farm()
            ->latestFirst()
            ->pluck('id')
            ->slice(52)
            ->values();

        if ($staleIds->isNotEmpty()) {
            AiAnalysis::whereIn('id', $staleIds)->delete();
            $this->info("Pruned {$staleIds->count()} old farm report(s).");
        }

        return self::SUCCESS;
    }
}
