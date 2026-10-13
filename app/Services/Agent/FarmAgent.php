<?php

namespace App\Services\Agent;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FarmAgent
{
    private const MAX_TOOL_ROUNDS = 4;

    public function __construct(private readonly FarmOperations $operations = new FarmOperations) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{text: string, sources: array<int, array{title: string, url: string}>, actions: array<int, string>, model: string}
     */
    public function reply(string $mode, array $history, string $message, string $language = 'en'): array
    {
        if (! config('gemini.api_key')) {
            throw AgentException::notConfigured();
        }

        $research = $mode === 'research';
        $language = $language === 'ur' ? 'ur' : 'en';
        $englishInUrduLetters = $language === 'en' && preg_match('/\p{Arabic}/u', $message) === 1;
        if ($language === 'en') {
            $history = array_values(array_filter(
                $history,
                fn (array $turn) => preg_match('/\p{Arabic}/u', (string) ($turn['content'] ?? '')) !== 1
            ));
        }
        $model = $research ? config('gemini.research_model') : config('gemini.operations_model');
        $contents = $this->contents($history, $message);
        $tools = $research
            ? [['googleSearch' => new \stdClass]]
            : [['functionDeclarations' => $this->operations->declarations()]];

        $actions = [];

        for ($round = 0; $round < ($research ? 1 : self::MAX_TOOL_ROUNDS); $round++) {
            $payload = $this->generate($model, $this->systemPrompt($research, $language, $englishInUrduLetters), $contents, $tools);
            $candidate = $payload['candidates'][0] ?? null;
            $content = $candidate['content'] ?? null;
            $parts = $content['parts'] ?? [];

            if (($candidate['finishReason'] ?? null) === 'SAFETY') {
                throw AgentException::declined();
            }

            $calls = $this->functionCalls($parts);
            if ($calls === [] || $research) {
                $text = $this->visibleText($parts);
                if ($text === '') {
                    throw AgentException::badResponse();
                }

                return [
                    'text' => $text,
                    'sources' => $this->sources($candidate['groundingMetadata'] ?? []),
                    'actions' => $actions,
                    'model' => $model,
                ];
            }

            $contents[] = $this->withObjectArgs($content);
            $responses = [];
            foreach ($calls as $call) {
                $name = (string) ($call['name'] ?? '');
                $args = is_array($call['args'] ?? null) ? $call['args'] : [];
                $result = $this->operations->run($name, $args);
                $actions[] = $result['summary'];
                $functionResponse = [
                    'name' => $name,
                    'response' => ['result' => $result],
                ];
                if (! empty($call['id'])) {
                    $functionResponse['id'] = $call['id'];
                }
                $responses[] = ['functionResponse' => $functionResponse];
            }
            $contents[] = ['role' => 'user', 'parts' => $responses];
        }

        throw AgentException::badResponse();
    }

    public function transcribe(string $audio, string $mime): string
    {
        if (! config('gemini.api_key')) {
            throw AgentException::notConfigured();
        }

        if ($audio === '') {
            return '';
        }

        $payload = $this->generate(
            (string) config('gemini.operations_model'),
            'You transcribe speech for a goat farm. Return only the words that were spoken.',
            [[
                'role' => 'user',
                'parts' => [
                    ['inlineData' => ['mimeType' => $mime, 'data' => base64_encode($audio)]],
                    ['text' => 'Transcribe this clip exactly. English stays English. Urdu stays Urdu script as spoken in Pakistan. Do not translate and do not answer the question.'],
                ],
            ]],
            [],
            ['temperature' => 0, 'maxOutputTokens' => 200],
        );

        $text = trim($this->visibleText($payload['candidates'][0]['content']['parts'] ?? []), " \n\t\"'");
        if ($text === '' || preg_match('/^(silence|\[silence\]|\(silence\)|no speech|\[no speech\])$/iu', $text) === 1) {
            return '';
        }

        return $text;
    }

    /**
     * @param  array<int, array<string, mixed>>  $contents
     * @param  array<int, mixed>  $tools
     * @param  array<string, mixed>  $generation
     * @return array<string, mixed>
     */
    private function generate(string $model, string $system, array $contents, array $tools, array $generation = []): array
    {
        $body = [
            'systemInstruction' => ['parts' => [['text' => $system]]],
            'contents' => $contents,
            'generationConfig' => array_merge([
                'temperature' => 0.3,
                'maxOutputTokens' => 400,
            ], $generation),
        ];
        if ($tools !== []) {
            $body['tools'] = $tools;
        }

        try {
            $response = null;
            for ($attempt = 1; $attempt <= 2; $attempt++) {
                $response = Http::withHeaders([
                    'x-goog-api-key' => config('gemini.api_key'),
                ])->timeout((int) config('gemini.timeout'))
                    ->acceptJson()
                    ->post('https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent', $body);

                if ($response->status() !== 503 || $attempt === 2) {
                    break;
                }

                usleep(750_000);
            }
        } catch (ConnectionException) {
            throw AgentException::unreachable();
        }

        if ($response->status() === 429) {
            throw AgentException::quota();
        }

        if ($response->status() === 503) {
            throw AgentException::busy();
        }

        if (! $response->successful()) {
            Log::warning('Gemini agent error.', ['status' => $response->status(), 'model' => $model]);
            throw AgentException::badResponse();
        }

        $json = $response->json();

        return is_array($json) ? $json : throw AgentException::badResponse();
    }

    /** @param  array<int, array{role: string, content: string}>  $history
     * @return array<int, array<string, mixed>>
     */
    private function contents(array $history, string $message): array
    {
        $contents = [];
        foreach (array_slice($history, -8) as $turn) {
            $text = trim((string) ($turn['content'] ?? ''));
            if ($text === '') {
                continue;
            }
            $role = ($turn['role'] ?? '') === 'model' ? 'model' : 'user';
            if ($role === 'model' && strlen($text) > 700) {
                $text = substr($text, 0, 700);
            }
            $last = $contents === [] ? null : $contents[array_key_last($contents)];
            if ($last && $last['role'] === $role) {
                continue;
            }
            $contents[] = ['role' => $role, 'parts' => [['text' => $text]]];
        }

        if ($contents !== [] && $contents[array_key_last($contents)]['role'] === 'user') {
            array_pop($contents);
        }

        $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

        return $contents;
    }

    private function systemPrompt(bool $research, string $language, bool $englishInUrduLetters): string
    {
        $today = now()->toDateString();

        if ($research) {
            return 'You are the research assistant for a working goat farm. Today is '.$today.'. '
                .'Answer questions about goat health, feed, breeding, and husbandry. Run Google Search before you answer, and base the answer on those results. '
                .'Prefer veterinary schools, extension services, and established livestock references. Name the source of any specific claim. '
                .'When sources disagree, say so. Describe published guidance, and tell the user to confirm drugs and doses with a veterinarian before treating an animal. '
                .'You cannot see or change this farm\'s records. If the question is about a specific goat on the farm, say that belongs in Operations mode. '
                .'Write in plain sentences a person can hear read aloud. Do not use markdown, asterisks, bold markers, or bullet symbols. '
                .'Answer in two short sentences.'
                .$this->languageLine($language, $englishInUrduLetters);
        }

        return 'You are the operations assistant for Mehran Goats. Today is '.$today.'. '
            .'Farm numbers come only from tools. Never invent a tag, weight, date, amount, or count. If a tool returns nothing, say that. '
            .'Call record_weight, record_health, or record_expense only when the user clearly asks you to save a record. Then confirm the tag and the values that were written. '
            .'You cannot delete records, sell a goat, or change ownership. Tell the user to use the matching screen for those. '
            .'Write in plain sentences a person can hear read aloud. Do not use markdown, asterisks, bold markers, or bullet symbols. '
            .'Answer in two short sentences and lead with the result. For a greeting or a question about what you can do, do not call a tool.'
            .$this->languageLine($language, $englishInUrduLetters);
    }

    private function languageLine(string $language, bool $englishInUrduLetters): string
    {
        if ($language === 'ur') {
            return ' The latest user message is Urdu. Reply only in Urdu as spoken in Pakistan, using Urdu script. Do not use Hindi. Ignore the language of earlier messages.';
        }

        $line = ' The latest user message is English. Reply only in English. Ignore the language of earlier messages.';
        if ($englishInUrduLetters) {
            $line .= ' Some of those English words were written in Urdu letters. Still answer in English.';
        }

        return $line;
    }

    /**
     * Gemini decodes an empty args object as a PHP list. Sending that list
     * back makes the next request invalid, so empty args stay an object.
     *
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private function withObjectArgs(array $content): array
    {
        foreach ($content['parts'] ?? [] as $index => $part) {
            if (! is_array($part) || ! isset($part['functionCall']) || ! is_array($part['functionCall'])) {
                continue;
            }
            if (($part['functionCall']['args'] ?? []) === []) {
                $part['functionCall']['args'] = new \stdClass;
                $content['parts'][$index] = $part;
            }
        }

        return $content;
    }

    /** @param  array<int, mixed>  $parts
     * @return array<int, array<string, mixed>>
     */
    private function functionCalls(array $parts): array
    {
        $calls = [];
        foreach ($parts as $part) {
            if (is_array($part) && isset($part['functionCall']['name'])) {
                $calls[] = $part['functionCall'];
            }
        }

        return $calls;
    }

    /** @param  array<int, mixed>  $parts */
    private function visibleText(array $parts): string
    {
        $chunks = [];
        foreach ($parts as $part) {
            if (! is_array($part) || ! empty($part['thought'])) {
                continue;
            }
            if (isset($part['text']) && is_string($part['text']) && trim($part['text']) !== '') {
                $chunks[] = trim($part['text']);
            }
        }

        return trim(implode("\n\n", $chunks));
    }

    /** @param  array<string, mixed>  $metadata
     * @return array<int, array{title: string, url: string}>
     */
    private function sources(array $metadata): array
    {
        $sources = [];
        foreach ($metadata['groundingChunks'] ?? [] as $chunk) {
            $web = $chunk['web'] ?? null;
            if (! is_array($web)) {
                continue;
            }
            $url = (string) ($web['uri'] ?? '');
            if (! str_starts_with($url, 'https://')) {
                continue;
            }
            $title = trim((string) ($web['title'] ?? ''));
            $sources[] = [
                'title' => $title !== '' ? $title : $url,
                'url' => $url,
            ];
        }

        $unique = [];
        foreach ($sources as $source) {
            $unique[$source['url']] = $source;
        }

        return array_values($unique);
    }
}
