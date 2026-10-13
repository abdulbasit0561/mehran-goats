<?php

namespace App\Http\Controllers;

use App\Models\AgentConversation;
use App\Models\AgentMessage;
use App\Services\Agent\AgentException;
use App\Services\Agent\FarmAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class AgentChatController extends Controller
{
    public function index(Request $request): View
    {
        $mode = $this->mode($request->query('mode'));
        $conversation = $this->latest($request->user()->id, $mode);

        $speakReply = null;
        if (session('speak') && $conversation) {
            $speakReply = $conversation->messages->where('role', 'model')->last();
        }

        return view('agent.index', [
            'mode' => $mode,
            'messages' => $conversation
                ? $conversation->messages->map(fn (AgentMessage $entry) => $this->payload($entry))->values()
                : [],
            'configured' => (bool) config('gemini.api_key'),
            'speakReply' => $speakReply,
            'model' => $mode === 'research'
                ? config('gemini.research_model')
                : config('gemini.operations_model'),
        ]);
    }

    public function store(Request $request, FarmAgent $agent): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'mode' => ['required', 'in:operational,research'],
            'message' => ['nullable', 'required_without:audio', 'string', 'max:2000'],
            'audio' => ['nullable', 'required_without:message', 'file', 'max:4096'],
            'transcript_only' => ['sometimes', 'boolean'],
        ]);

        $message = trim((string) ($data['message'] ?? ''));
        if ($request->file('audio')) {
            try {
                $message = $agent->transcribe(
                    $request->file('audio')->getContent(),
                    $this->audioMime($request->file('audio')),
                );
            } catch (AgentException $e) {
                return $this->fail($request, $data['mode'], $e->getMessage());
            }

            if ($message === '') {
                return $this->fail($request, $data['mode'], 'I did not catch that. Speak again.');
            }
        }

        if ($request->boolean('transcript_only')) {
            return response()->json(['transcript' => $message]);
        }

        $conversation = $this->latest($request->user()->id, $data['mode'])
            ?? AgentConversation::create([
                'user_id' => $request->user()->id,
                'mode' => $data['mode'],
            ]);

        $history = $conversation->messages()
            ->latest('id')
            ->limit(12)
            ->get()
            ->reverse()
            ->map(fn ($row) => ['role' => $row->role, 'content' => $row->content])
            ->values()
            ->all();

        try {
            $result = $agent->reply($data['mode'], $history, $message, $this->languageOf($message));
        } catch (AgentException $e) {
            return $this->fail($request, $data['mode'], $e->getMessage());
        }

        $userMessage = $conversation->messages()->create([
            'role' => 'user',
            'content' => $message,
        ]);

        if (! $conversation->title) {
            $conversation->update(['title' => str($message)->limit(80)->toString()]);
        }

        $reply = $conversation->messages()->create([
            'role' => 'model',
            'content' => $result['text'],
            'sources' => $result['sources'],
            'actions' => $result['actions'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'user' => $this->payload($userMessage),
                'reply' => $this->payload($reply),
            ]);
        }

        return redirect()
            ->route('agent.index', ['mode' => $data['mode']])
            ->with('speak', true);
    }

    public function reset(Request $request): RedirectResponse
    {
        $mode = $this->mode($request->input('mode'));
        $latest = $this->latest($request->user()->id, $mode);

        if ($latest && $latest->messages()->exists()) {
            AgentConversation::create([
                'user_id' => $request->user()->id,
                'mode' => $mode,
            ]);
        }

        return redirect()->route('agent.index', ['mode' => $mode]);
    }

    private function fail(Request $request, string $mode, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return redirect()
            ->route('agent.index', ['mode' => $mode])
            ->withInput()
            ->with('error', $message);
    }

    private function audioMime(UploadedFile $file): string
    {
        $map = [
            'audio/webm' => 'audio/webm',
            'video/webm' => 'audio/webm',
            'audio/ogg' => 'audio/ogg',
            'audio/wav' => 'audio/wav',
            'audio/x-wav' => 'audio/wav',
            'audio/wave' => 'audio/wav',
            'audio/mpeg' => 'audio/mp3',
            'audio/mp3' => 'audio/mp3',
            'audio/mp4' => 'audio/mp4',
            'video/mp4' => 'audio/mp4',
            'audio/aac' => 'audio/aac',
        ];

        foreach ([$file->getMimeType(), $file->getClientMimeType()] as $mime) {
            $mime = strtolower(trim(explode(';', (string) $mime)[0]));
            if (isset($map[$mime])) {
                return $map[$mime];
            }
        }

        return match (strtolower($file->getClientOriginalExtension())) {
            'wav' => 'audio/wav',
            'mp3' => 'audio/mp3',
            'mp4', 'm4a' => 'audio/mp4',
            'ogg' => 'audio/ogg',
            'aac' => 'audio/aac',
            default => 'audio/webm',
        };
    }

    private function languageOf(string $message): string
    {
        $message = trim($message);
        if ($message === '') {
            return 'en';
        }

        if (preg_match('/\p{Arabic}/u', $message) === 1) {
            return $this->urduScriptIsEnglish($message) ? 'en' : 'ur';
        }

        return $this->romanIsUrdu($message) ? 'ur' : 'en';
    }

    private function urduScriptIsEnglish(string $message): bool
    {
        $english = preg_match_all('/ہیلو|ہلو|ہاؤ|ہاو|ایجنٹ|ایجن|آر|ار|یو/u', $message);
        $urdu = preg_match_all('/میں|ہوں|ہیں|آپ|بتا|کیا|کتنی|کتنا|بکری|ریوڑ|فارم|شکریہ|سلام/u', $message);

        return $english > 0 && $urdu === 0;
    }

    private function romanIsUrdu(string $message): bool
    {
        $words = preg_split('/\s+/u', mb_strtolower($message)) ?: [];
        $markers = ['salam', 'assalamualaikum', 'shukriya', 'kitni', 'kitne', 'bakri', 'bakrian', 'batao', 'mujhe', 'kya', 'kahan', 'tumhare', 'tumhara', 'kaise', 'kaisa', 'hal', 'hai'];
        $hits = 0;
        foreach ($words as $word) {
            $word = trim($word, ".,!?");
            if (in_array($word, $markers, true)) {
                $hits++;
            }
        }

        return $hits >= 2 && $hits * 2 >= count($words);
    }

    private function payload(AgentMessage $entry): array
    {
        return [
            'id' => $entry->id,
            'role' => $entry->role,
            'content' => $entry->content,
            'sources' => $entry->sources ?? [],
            'actions' => $entry->actions ?? [],
        ];
    }

    private function mode(mixed $mode): string
    {
        return $mode === 'research' ? 'research' : 'operational';
    }

    private function latest(int $userId, string $mode): ?AgentConversation
    {
        return AgentConversation::query()
            ->where('user_id', $userId)
            ->where('mode', $mode)
            ->latest('id')
            ->first();
    }
}
