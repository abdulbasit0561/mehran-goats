<?php

namespace App\Services\Ai;

use App\Models\Goat;

/**
 * Goat-farm health advisor persona. Builds prompts, calls the AI client,
 * and normalizes responses to the agreed payload schemas.
 */
class AiHealthAdvisor
{
    public function __construct(
        private readonly AiClient $client = new AiClient,
        private readonly AiHerdDataCollector $collector = new AiHerdDataCollector,
    ) {}

    /**
     * Per-goat analysis. Returns:
     * { insights: [{title, detail, severity: info|warning|alert}],
     *   suggestions: [{title, detail}] }
     */
    public function analyzeGoat(Goat $goat): array
    {
        $context = $this->collector->goatContext($goat);

        $result = $this->client->chat([
            ['role' => 'system', 'content' => self::systemPrompt()],
            ['role' => 'user', 'content' => "Analyze this goat's health and care. Data:\n".json_encode($context, JSON_UNESCAPED_SLASHES)],
        ]);

        return $this->normalizeGoatPayload($result);
    }

    /**
     * Farm-wide analysis. Returns goat payload plus:
     * recommendations: [{area: feeding|breeding|disease_prevention, title, detail}]
     * alerts: [{title, detail, severity}]
     */
    public function analyzeFarm(): array
    {
        $context = $this->collector->farmContext();

        $result = $this->client->chat([
            ['role' => 'system', 'content' => self::systemPrompt(true)],
            ['role' => 'user', 'content' => "Produce a herd health report for this goat farm. Data:\n".json_encode($context, JSON_UNESCAPED_SLASHES)],
        ]);

        return $this->normalizeFarmPayload($result);
    }

    private static function systemPrompt(bool $farm = false): string
    {
        $base = 'You are an experienced goat farm health advisor. You receive JSON data about '
            .'goats (weights, vaccinations, treatments, symptoms, breeding) and must produce practical, '
            .'specific guidance. Everything you write is advisory — it supports farm management decisions '
            .'but is not a substitute for professional veterinary diagnosis; when symptoms suggest serious '
            .'illness, recommend contacting a veterinarian. Base every insight on the data provided — cite '
            .'concrete numbers (weights, dates, gaps). If the goat\'s status is "sold" or "deceased", do NOT '
            .'suggest future care plans — focus only on what the record shows or lessons for the herd. '
            .'Keep output concise (under 600 words total). '
            .'Respond with a single valid JSON object in strict JSON only. No prose outside the JSON.';

        $schema = ' JSON schema: {"insights":[{"title":string,"detail":string,"severity":"info"|"warning"|"alert"}],'
            .'"suggestions":[{"title":string,"detail":string}]}';

        if ($farm) {
            $schema = ' JSON schema: {"insights":[{"title":string,"detail":string,"severity":"info"|"warning"|"alert"}],'
                .'"suggestions":[{"title":string,"detail":string}],'
                .'"alerts":[{"title":string,"detail":string,"severity":"info"|"warning"|"alert"}],'
                .'"recommendations":[{"area":"feeding"|"breeding"|"disease_prevention","title":string,"detail":string}]}';
        }

        return $base.$schema;
    }

    private function normalizeGoatPayload(array $result): array
    {
        return [
            'insights'    => self::items($result, 'insights', ['title', 'detail', 'severity']),
            'suggestions' => self::items($result, 'suggestions', ['title', 'detail']),
        ];
    }

    private function normalizeFarmPayload(array $result): array
    {
        return [
            'insights'        => self::items($result, 'insights', ['title', 'detail', 'severity']),
            'suggestions'     => self::items($result, 'suggestions', ['title', 'detail']),
            'alerts'          => self::items($result, 'alerts', ['title', 'detail', 'severity']),
            'recommendations' => self::items($result, 'recommendations', ['title', 'detail', 'area']),
        ];
    }

    /** Keep only well-formed items with whitelisted keys. */
    private static function items(array $result, string $key, array $fields): array
    {
        $rows = $result[$key] ?? [];

        if (! is_array($rows)) {
            return [];
        }

        $clean = [];
        foreach ($rows as $row) {
            if (! is_array($row) || empty($row['title']) || empty($row['detail'])) {
                continue;
            }
            $item = [];
            foreach ($fields as $f) {
                $item[$f] = is_scalar($row[$f] ?? null) ? (string) $row[$f] : null;
            }
            if (isset($item['severity']) && ! in_array($item['severity'], ['info', 'warning', 'alert'], true)) {
                $item['severity'] = 'info';
            }
            if (isset($item['area']) && ! in_array($item['area'], ['feeding', 'breeding', 'disease_prevention'], true)) {
                $item['area'] = 'disease_prevention';
            }
            $clean[] = $item;
        }

        return $clean;
    }
}
