<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Farm agent</h2>
    </x-slot>

    <div class="py-6 sm:py-10 page-content">
        <div class="max-w-3xl mx-auto px-4 sm:px-6"
             x-data="agentChat({
                initial: @js(old('message', '')),
                configured: @js($configured),
                autoSpeak: @js($speakReply ? ['id' => $speakReply->id, 'text' => $speakReply->content] : null),
                mode: @js($mode),
                messages: @js($messages),
                endpoint: @js(route('agent.messages.store')),
                speaker: @js($mode === 'research' ? 'Research' : 'Operations'),
                initialError: @js(session('error')),
             })"
             x-on:fill-prompt.window="text = $event.detail">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h3 class="text-2xl font-semibold text-[#1b3a2b] tracking-tight">Ask the farm</h3>
                    <p class="mt-1 text-sm text-gray-600 max-w-xl">
                        @if ($mode === 'operational')
                            Press Talk and speak. A pause sends the phrase here, Operations answers aloud, then the microphone opens again.
                        @else
                            Press Talk and ask about goat health or feed. Research answers aloud, then listens for the next question. It does not open the farm records.
                        @endif
                    </p>
                </div>
                <p class="text-xs text-gray-500">{{ $model }}</p>
            </div>

            <div class="mt-5 grid grid-cols-2 rounded-lg overflow-hidden border border-[#1b3a2b]/15">
                <a href="{{ route('agent.index', ['mode' => 'operational']) }}"
                   class="px-4 py-3 text-sm font-medium text-center {{ $mode === 'operational' ? 'bg-[#1b3a2b] text-white' : 'bg-white text-[#1b3a2b] hover:bg-[#f5f0e6]' }}">
                    Operations
                </a>
                <a href="{{ route('agent.index', ['mode' => 'research']) }}"
                   class="px-4 py-3 text-sm font-medium text-center {{ $mode === 'research' ? 'bg-[#c2410c] text-white' : 'bg-white text-[#1b3a2b] hover:bg-[#f5f0e6]' }}">
                    Research
                </a>
            </div>

            <div class="mt-4">
                <x-ai-disclaimer />
            </div>

            @if (! $configured)
                <p class="mt-4 text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">
                    Gemini is not configured yet. Add a key as GEMINI_API_KEY before asking a question.
                </p>
            @endif

            <p x-show="error" x-cloak class="mt-4 text-sm text-red-800 bg-red-50 border border-red-200 rounded-md px-3 py-2" x-text="error"></p>

            <div id="agent-transcript" class="mt-6 space-y-4 min-h-[16rem]" x-cloak>
                <div x-show="messages.length === 0" class="rounded-lg border border-dashed border-[#1b3a2b]/20 px-4 py-8">
                    <p class="text-sm text-gray-600">
                        @if ($mode === 'operational')
                            Try a herd question, or tell it to record something you just measured.
                        @else
                            Ask a health or feed question. The answer will name the pages it used.
                        @endif
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($mode === 'operational'
                            ? ['Which goats are overdue for vaccination?', 'Summarize feed and vet spend for the last 3 months.']
                            : ['What should a growing meat kid be fed?', 'What are the usual signs of enterotoxemia in goats?'] as $prompt)
                            <button type="button"
                                    class="text-left text-sm px-3 py-2 rounded-md bg-white border border-gray-200 text-[#1b3a2b] hover:border-[#1b3a2b]"
                                    @click="$dispatch('fill-prompt', @js($prompt))">{{ $prompt }}</button>
                        @endforeach
                    </div>
                </div>
                <template x-for="entry in messages" :key="entry.id">
                    <article :class="entry.role === 'user' ? 'ml-8 sm:ml-16' : 'mr-8 sm:mr-16'">
                        <div class="flex items-center gap-2 mb-1">
                            <p class="text-xs text-gray-500" x-text="entry.role === 'user' ? 'You' : speaker"></p>
                            <button type="button" x-show="entry.role === 'model'"
                                    @click="toggleSpeak(entry.id, entry.content)"
                                    class="text-xs font-medium text-[#1b3a2b] underline underline-offset-2">
                                <span x-text="speakingId === entry.id ? 'Stop' : 'Listen'"></span>
                            </button>
                        </div>
                        <div class="rounded-lg px-4 py-3 text-sm leading-relaxed"
                             :class="entry.role === 'user' ? 'bg-[#f5f0e6] text-[#1b3a2b]' : 'bg-white border border-gray-200 text-gray-800'"
                             x-html="format(entry.content)"></div>
                        <ul class="mt-2 space-y-1" x-show="entry.actions && entry.actions.length">
                            <template x-for="(action, index) in entry.actions" :key="entry.id + '-action-' + index">
                                <li class="text-xs text-[#1b3a2b]" x-text="action"></li>
                            </template>
                        </ul>
                        <ul class="mt-2 space-y-1" x-show="entry.sources && entry.sources.length">
                            <template x-for="source in entry.sources" :key="source.url">
                                <li>
                                    <a :href="String(source.url).startsWith('https://') ? source.url : '#'"
                                       target="_blank" rel="noopener noreferrer"
                                       class="text-xs text-[#c2410c] underline underline-offset-2"
                                       x-text="source.title || source.url"></a>
                                </li>
                            </template>
                        </ul>
                    </article>
                </template>
            </div>

            <form id="agent-form" class="mt-6" @submit.prevent="send()">
                <label for="agent-message" class="sr-only">Message</label>
                <textarea id="agent-message" name="message" rows="3" x-model="text" required maxlength="2000"
                          placeholder="{{ $mode === 'operational' ? 'Talk or type in English or Urdu.' : 'Talk or type a health or feed question in English or Urdu.' }}"
                          class="w-full rounded-lg border px-3 py-2 text-sm text-gray-900 focus:border-[#1b3a2b] focus:ring-[#1b3a2b]"
                          :class="listening ? 'border-red-400' : 'border-gray-300'"></textarea>
                <p class="mt-1 text-xs min-h-[1rem]" :class="listening || speakingId ? 'text-red-700' : 'text-gray-500'" x-text="voiceNote"></p>
                @error('message')
                    <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                @enderror
                <div class="mt-2 flex items-center justify-between gap-3">
                    <button type="submit" form="agent-reset" class="text-sm text-gray-500 hover:text-gray-800" :disabled="sending">Clear this chat</button>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="toggleTalk()" :disabled="!voiceSupported || (sending && !talking)"
                                :aria-pressed="talking"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium border disabled:opacity-50"
                                :class="talking ? 'bg-red-600 text-white border-red-600' : 'bg-white text-[#1b3a2b] border-gray-300 hover:border-[#1b3a2b]'">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z"/>
                            </svg>
                            <span x-text="talking ? 'End' : 'Talk'"></span>
                        </button>
                        <button type="button" @click="toggleVoice()" :disabled="sending || talking || !voiceSupported"
                                :aria-pressed="listening && !talking"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium border disabled:opacity-50"
                                :class="listening && !talking ? 'bg-red-600 text-white border-red-600' : 'bg-white text-[#1b3a2b] border-gray-300 hover:border-[#1b3a2b]'">
                            <span x-text="listening && !talking ? 'Stop' : 'Dictate'"></span>
                        </button>
                        <button type="submit" :disabled="sending || !configured"
                                class="px-4 py-2 rounded-md text-sm font-medium text-white disabled:opacity-50 {{ $mode === 'research' ? 'bg-[#c2410c] hover:bg-[#9a3412]' : 'bg-[#1b3a2b] hover:bg-[#244736]' }}">
                            <span x-show="!sending">Send</span>
                            <span x-show="sending" x-cloak>Working…</span>
                        </button>
                    </div>
                </div>
            </form>
            <script>
                document.addEventListener('alpine:init', () => {
                    Alpine.data('agentChat', ({ initial, configured, autoSpeak, mode, messages, endpoint, speaker, initialError }) => ({
                        text: initial || '',
                        sending: false,
                        configured,
                        autoSpeak,
                        mode,
                        messages,
                        endpoint,
                        speaker,
                        error: initialError || '',
                        quietReply: false,
                        lang: 'en',
                        speechLang: 'en',
                        talking: false,
                        listening: false,
                        speakingId: null,
                        voiceSupported: false,
                        voiceNote: '',
                        stream: null,
                        recorder: null,
                        audioContext: null,
                        meterTimer: null,
                        chunks: [],
                        heardSpeech: false,
                        micToken: 0,
                        openingMic: false,
                        recordPurpose: 'talk',
                        ignoreEnd: false,
                        speakToken: 0,
                        speakTimer: null,
                        thenListen: false,
                        currentUtterance: null,
                        init() {
                            this.talking = sessionStorage.getItem(this.talkStorageKey()) === this.mode;
                            this.lang = this.startingLanguage();
                            this.setupVoice();
                            if (this.autoSpeak?.text) {
                                this.speakWhenReady(this.autoSpeak.id, this.autoSpeak.text, this.talking);
                            } else if (this.talking) {
                                this.beginListen();
                            }
                        },
                        talkStorageKey() {
                            return 'mehran-agent-talk';
                        },
                        setupVoice() {
                            this.voiceSupported = Boolean(navigator.mediaDevices?.getUserMedia && window.MediaRecorder);
                            if (!this.voiceSupported) {
                                this.voiceNote = 'Voice conversation needs Chrome or Edge.';
                                this.talking = false;
                            }
                        },
                        toggleTalk() {
                            if (this.talking) {
                                this.endTalk();
                                this.voiceNote = this.sending ? 'One moment…' : '';
                                return;
                            }
                            if (this.sending || !this.voiceSupported) {
                                return;
                            }
                            this.talking = true;
                            sessionStorage.setItem(this.talkStorageKey(), this.mode);
                            this.text = '';
                            this.beginListen();
                        },
                        endTalk(note = '') {
                            if (this.sending) {
                                this.quietReply = true;
                            }
                            this.talking = false;
                            sessionStorage.removeItem(this.talkStorageKey());
                            this.stopVoice();
                            this.stopSpeak();
                            if (note) {
                                this.voiceNote = note;
                            }
                        },
                        async send(audioBlob = null) {
                            const message = audioBlob ? '' : this.text.trim();
                            if ((!message && !audioBlob) || this.sending || !this.configured) {
                                return;
                            }
                            this.sending = true;
                            this.error = '';
                            this.stopVoice();
                            this.stopSpeak();
                            const pendingId = 'pending-' + Date.now();
                            this.messages.push({
                                id: pendingId,
                                role: 'user',
                                content: message || '…',
                                sources: [],
                                actions: [],
                            });
                            if (!audioBlob) {
                                this.text = '';
                            }
                            this.voiceNote = 'One moment…';
                            this.$nextTick(() => this.scrollTranscript());
                            try {
                                const headers = {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                };
                                let body;
                                if (audioBlob) {
                                    body = new FormData();
                                    body.append('mode', this.mode);
                                    body.append('audio', audioBlob, this.audioName(audioBlob));
                                } else {
                                    headers['Content-Type'] = 'application/json';
                                    body = JSON.stringify({ mode: this.mode, message });
                                }
                                const response = await fetch(this.endpoint, {
                                    method: 'POST',
                                    headers,
                                    body,
                                });
                                const data = await response.json().catch(() => ({}));
                                if (!response.ok) {
                                    this.dropPending(pendingId, audioBlob ? '' : message, data.message || 'The agent could not answer. Try again.');
                                    return;
                                }
                                this.messages = this.messages.filter((entry) => entry.id !== pendingId);
                                this.messages.push(data.user, data.reply);
                                this.useDetectedLanguage(data.user?.content || message);
                                const resume = this.talking && !this.quietReply;
                                const quiet = this.quietReply;
                                this.quietReply = false;
                                this.sending = false;
                                this.voiceNote = '';
                                this.$nextTick(() => this.scrollTranscript());
                                if (!quiet && data.reply?.content) {
                                    this.speak(data.reply.id, data.reply.content, resume);
                                } else if (resume) {
                                    this.beginListen();
                                }
                            } catch (error) {
                                this.dropPending(pendingId, audioBlob ? '' : message, 'The agent could not answer. Check the connection and try again.');
                            }
                        },
                        dropPending(pendingId, message, note) {
                            this.messages = this.messages.filter((entry) => entry.id !== pendingId);
                            if (message) {
                                this.text = message;
                            }
                            this.error = note;
                            this.voiceNote = '';
                            this.sending = false;
                            this.quietReply = false;
                            if (this.talking) {
                                setTimeout(() => this.beginListen(), 400);
                            }
                        },
                        scrollTranscript() {
                            document.getElementById('agent-transcript')?.lastElementChild?.scrollIntoView({ block: 'nearest' });
                        },
                        format(content) {
                            const lines = String(content ?? '')
                                .replace(/\r\n/g, '\n')
                                .replace(/\s+\*\s+(?=\*\*|[A-Z])/g, '\n* ')
                                .split('\n');
                            let html = '';
                            let list = [];
                            const flush = () => {
                                if (!list.length) {
                                    return;
                                }
                                html += '<ul class="mt-2 list-disc list-inside space-y-1">'
                                    + list.map((item) => '<li>' + this.inline(item) + '</li>').join('')
                                    + '</ul>';
                                list = [];
                            };
                            lines.forEach((line) => {
                                const item = line.match(/^\s*(?:[-*•]|\d+\.)\s+(.*)$/);
                                if (item) {
                                    list.push(item[1]);
                                    return;
                                }
                                flush();
                                if (line.trim() !== '') {
                                    html += '<p class="' + (html ? 'mt-2' : '') + '">' + this.inline(line) + '</p>';
                                }
                            });
                            flush();
                            return html;
                        },
                        inline(text) {
                            return this.escapeHtml(text)
                                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                                .replace(/(^|[\s])\*([^*\n]+)\*(?=[\s.,:;]|$)/g, '$1<em>$2</em>');
                        },
                        escapeHtml(text) {
                            return String(text)
                                .replace(/&/g, '&amp;')
                                .replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;');
                        },
                        startingLanguage() {
                            const fromChat = this.detectLanguage(this.latestUserText());
                            if (fromChat === 'ur') {
                                return 'ur';
                            }
                            const browser = navigator.languages || [navigator.language || ''];
                            return browser.some((code) => /^ur([-_]|$)/i.test(code)) ? 'ur' : 'en';
                        },
                        latestUserText() {
                            const last = [...this.messages].reverse().find((entry) => entry.role === 'user');
                            return last?.content || '';
                        },
                        detectLanguage(text) {
                            const value = String(text || '').trim();
                            if (!value) {
                                return 'en';
                            }
                            if (/[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF]/.test(value)) {
                                return this.urduScriptIsEnglish(value) ? 'en' : 'ur';
                            }
                            return this.romanIsUrdu(value) ? 'ur' : 'en';
                        },
                        urduScriptIsEnglish(value) {
                            const english = value.match(/ہیلو|ہلو|ہاؤ|ہاو|ایجنٹ|ایجن|آر|ار|یو/g) || [];
                            const urdu = value.match(/میں|ہوں|ہیں|آپ|بتا|کیا|کتنی|کتنا|بکری|ریوڑ|فارم|شکریہ|سلام/g) || [];
                            return english.length > 0 && urdu.length === 0;
                        },
                        romanIsUrdu(value) {
                            const words = value.toLowerCase().split(/\s+/).map((word) => word.replace(/[.,!?]/g, ''));
                            const markers = new Set(['salam', 'assalamualaikum', 'shukriya', 'kitni', 'kitne', 'bakri', 'bakrian', 'batao', 'mujhe', 'kya', 'kahan', 'tumhare', 'tumhara', 'kaise', 'kaisa', 'hal', 'hai']);
                            const hits = words.filter((word) => markers.has(word)).length;
                            return hits >= 2 && hits * 2 >= words.length;
                        },
                        useDetectedLanguage(text) {
                            this.lang = this.detectLanguage(text);
                            if (this.voiceNote.startsWith('No Urdu') && this.lang !== 'ur') {
                                this.voiceNote = '';
                            }
                        },
                        spoken(text) {
                            return String(text ?? '')
                                .replace(/\s+\*\s+(?=\*\*|[A-Z])/g, ' ')
                                .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1')
                                .replace(/\*\*(.+?)\*\*/g, '$1')
                                .replace(/(^|[\s])\*([^*\n]+)\*(?=[\s.,:;]|$)/g, '$1$2')
                                .replace(/^\s*(?:[-*•]|\d+\.)\s+/gm, '')
                                .replace(/[*#`_]+/g, '')
                                .replace(/\s+([.,;:])/g, '$1')
                                .replace(/\s+/g, ' ')
                                .trim();
                        },
                        beginListen() {
                            if (!this.talking || this.sending || this.listening || this.openingMic) {
                                return;
                            }
                            this.stopSpeak();
                            this.text = '';
                            this.openMic('talk');
                        },
                        toggleVoice() {
                            if (this.sending || this.talking || !this.voiceSupported) {
                                return;
                            }
                            if (this.listening) {
                                this.finishRecording();
                                return;
                            }
                            this.stopSpeak();
                            this.voiceNote = '';
                            this.openMic('dictate');
                        },
                        async openMic(purpose) {
                            this.stopVoice();
                            const token = ++this.micToken;
                            this.openingMic = true;
                            this.recordPurpose = purpose;
                            let stream;
                            try {
                                stream = await navigator.mediaDevices.getUserMedia({
                                    audio: { echoCancellation: true, noiseSuppression: true },
                                });
                            } catch (error) {
                                this.openingMic = false;
                                this.micFailed(error);
                                return;
                            }
                            if (token !== this.micToken || (purpose === 'talk' && !this.talking)) {
                                stream.getTracks().forEach((track) => track.stop());
                                this.openingMic = false;
                                return;
                            }
                            const mime = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4']
                                .find((type) => MediaRecorder.isTypeSupported(type)) || '';
                            const recorder = new MediaRecorder(stream, mime ? { mimeType: mime } : undefined);
                            const context = new AudioContext();
                            await context.resume();
                            if (token !== this.micToken || (purpose === 'talk' && !this.talking)) {
                                stream.getTracks().forEach((track) => track.stop());
                                context.close().catch(() => {});
                                this.openingMic = false;
                                return;
                            }
                            const source = context.createMediaStreamSource(stream);
                            const analyser = context.createAnalyser();
                            analyser.fftSize = 2048;
                            source.connect(analyser);
                            const samples = new Uint8Array(analyser.fftSize);
                            const chunks = [];
                            let heard = false;
                            let quietAt = 0;
                            const started = performance.now();
                            recorder.ondataavailable = (event) => {
                                if (event.data && event.data.size) {
                                    chunks.push(event.data);
                                }
                            };
                            recorder.onstop = () => {
                                const blob = new Blob(chunks, { type: recorder.mimeType || 'audio/webm' });
                                stream.getTracks().forEach((track) => track.stop());
                                context.close().catch(() => {});
                                if (token !== this.micToken) {
                                    return;
                                }
                                this.listening = false;
                                this.recorder = null;
                                this.stream = null;
                                this.audioContext = null;
                                if (!heard || blob.size < 200) {
                                    return;
                                }
                                if (purpose === 'dictate') {
                                    this.transcribe(blob);
                                } else if (this.talking) {
                                    this.send(blob);
                                }
                            };
                            this.stream = stream;
                            this.recorder = recorder;
                            this.audioContext = context;
                            this.chunks = chunks;
                            this.heardSpeech = false;
                            recorder.start(200);
                            this.listening = true;
                            this.openingMic = false;
                            this.voiceNote = purpose === 'talk' ? 'Your turn. Speak, then pause.' : 'Listening…';
                            this.meterTimer = setInterval(() => {
                                if (token !== this.micToken || recorder.state !== 'recording') {
                                    return;
                                }
                                analyser.getByteTimeDomainData(samples);
                                let sum = 0;
                                for (let index = 0; index < samples.length; index++) {
                                    const sample = (samples[index] - 128) / 128;
                                    sum += sample * sample;
                                }
                                const loud = Math.sqrt(sum / samples.length) > 0.02;
                                if (loud) {
                                    heard = true;
                                    this.heardSpeech = true;
                                    quietAt = 0;
                                } else if (heard && purpose === 'talk') {
                                    quietAt = quietAt || performance.now();
                                    if (performance.now() - quietAt > 900) {
                                        this.finishRecording();
                                    }
                                }
                                if (performance.now() - started > 20000) {
                                    if (heard) {
                                        this.finishRecording();
                                    } else if (purpose === 'talk' && this.talking) {
                                        this.stopVoice();
                                        setTimeout(() => this.beginListen(), 250);
                                    } else {
                                        this.stopVoice();
                                    }
                                }
                            }, 100);
                        },
                        finishRecording() {
                            if (this.meterTimer) {
                                clearInterval(this.meterTimer);
                                this.meterTimer = null;
                            }
                            if (this.recorder && this.recorder.state === 'recording') {
                                try {
                                    this.recorder.stop();
                                } catch (error) {
                                    this.listening = false;
                                }
                            }
                        },
                        micFailed(error) {
                            const name = error && error.name;
                            if (name === 'NotAllowedError' || name === 'SecurityError') {
                                this.endTalk('Allow the microphone, then press Talk again.');
                                return;
                            }
                            if (name === 'NotFoundError' || name === 'OverconstrainedError') {
                                this.endTalk('No microphone was found.');
                                return;
                            }
                            this.endTalk('The microphone did not start. Press Talk again.');
                        },
                        async transcribe(blob) {
                            this.sending = true;
                            this.voiceNote = 'One moment…';
                            const body = new FormData();
                            body.append('mode', this.mode);
                            body.append('transcript_only', '1');
                            body.append('audio', blob, this.audioName(blob));
                            try {
                                const response = await fetch(this.endpoint, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                    },
                                    body,
                                });
                                const data = await response.json().catch(() => ({}));
                                if (!response.ok) {
                                    this.voiceNote = data.message || 'Voice input could not be transcribed. Try again.';
                                    return;
                                }
                                const transcript = String(data.transcript || '').trim();
                                if (!transcript) {
                                    this.voiceNote = 'I did not catch that. Try again.';
                                    return;
                                }
                                this.text = this.text.trim() ? this.text.trim() + ' ' + transcript : transcript;
                                this.useDetectedLanguage(transcript);
                                this.voiceNote = 'Check the words, then send.';
                            } catch (error) {
                                this.voiceNote = 'Voice input could not be transcribed. Try again.';
                            } finally {
                                this.sending = false;
                            }
                        },
                        audioName(blob) {
                            const type = String(blob.type || '');
                            if (type.includes('mp4')) {
                                return 'speech.mp4';
                            }
                            if (type.includes('ogg')) {
                                return 'speech.ogg';
                            }
                            if (type.includes('wav')) {
                                return 'speech.wav';
                            }
                            return 'speech.webm';
                        },
                        speakWhenReady(id, text, thenListen = false) {
                            if (!window.speechSynthesis) {
                                if (thenListen) {
                                    this.beginListen();
                                }
                                return;
                            }
                            let started = false;
                            const start = () => {
                                if (started) {
                                    return;
                                }
                                started = true;
                                this.speak(id, text, thenListen);
                            };
                            if (window.speechSynthesis.getVoices().length) {
                                start();
                                return;
                            }
                            window.speechSynthesis.addEventListener('voiceschanged', start, { once: true });
                            setTimeout(start, 400);
                        },
                        toggleSpeak(id, text) {
                            if (this.speakingId === id) {
                                this.stopSpeak();
                                return;
                            }
                            this.speak(id, text, this.talking);
                        },
                        speak(id, text, thenListen = false) {
                            if (!window.speechSynthesis) {
                                this.voiceNote = 'This browser cannot read replies aloud.';
                                if (thenListen) {
                                    this.beginListen();
                                }
                                return;
                            }
                            this.stopVoice();
                            this.stopSpeak();
                            const chunks = this.speechChunks(this.spoken(text));
                            this.speechLang = this.detectLanguage(text);
                            const voice = this.voiceFor(this.speechLang);
                            if (this.speechLang === 'ur' && !voice) {
                                this.voiceNote = 'No Urdu (Pakistan) voice is installed. Add Urdu (Pakistan), not Hindi.';
                                if (thenListen && this.talking) {
                                    setTimeout(() => this.beginListen(), 300);
                                }
                                return;
                            }
                            if (!chunks.length) {
                                if (thenListen && this.talking) {
                                    this.beginListen();
                                }
                                return;
                            }
                            if (this.talking) {
                                this.voiceNote = 'Speaking…';
                            }
                            this.speakingId = id;
                            this.thenListen = thenListen;
                            const token = this.speakToken;
                            this.speakTimer = setTimeout(() => this.speakChunk(chunks, 0, token), 80);
                        },
                        speakChunk(chunks, index, token) {
                            if (token !== this.speakToken || index >= chunks.length) {
                                if (token === this.speakToken && index >= chunks.length) {
                                    this.finishSpeaking();
                                }
                                return;
                            }
                            const utterance = new SpeechSynthesisUtterance(chunks[index]);
                            utterance.lang = this.speechLang === 'ur' ? 'ur-PK' : 'en-US';
                            utterance.rate = 1;
                            utterance.pitch = 1;
                            const voice = this.voiceFor(this.speechLang);
                            if (voice) {
                                utterance.voice = voice;
                            }
                            utterance.onend = () => {
                                if (token !== this.speakToken) {
                                    return;
                                }
                                this.speakChunk(chunks, index + 1, token);
                            };
                            utterance.onerror = (event) => {
                                if (token !== this.speakToken || event.error === 'interrupted' || event.error === 'canceled') {
                                    return;
                                }
                                this.finishSpeaking();
                            };
                            this.currentUtterance = utterance;
                            window.speechSynthesis.speak(utterance);
                        },
                        speechChunks(text) {
                            const clean = String(text || '').replace(/\s+/g, ' ').trim();
                            if (!clean) {
                                return [];
                            }
                            const chunks = [];
                            let current = '';
                            const push = (value) => {
                                const piece = value.trim();
                                if (piece) {
                                    chunks.push(piece);
                                }
                            };
                            clean.split(/(?<=[.!?۔؟])\s+/).forEach((sentence) => {
                                if (sentence.length > 170) {
                                    push(current);
                                    current = '';
                                    sentence.split(/،\s*|,\s*/).forEach((bit) => push(bit));
                                    return;
                                }
                                const next = (current + ' ' + sentence).trim();
                                if (next.length > 170) {
                                    push(current);
                                    current = sentence;
                                } else {
                                    current = next;
                                }
                            });
                            push(current);
                            return chunks.slice(0, 8);
                        },
                        voiceFor(lang) {
                            const voices = window.speechSynthesis?.getVoices?.() || [];
                            const matching = voices.filter((voice) => {
                                const code = String(voice.lang || '');
                                if (/^hi([-_]|$)/i.test(code)) {
                                    return false;
                                }
                                return lang === 'ur' ? /^ur([-_]|$)/i.test(code) : /^en([-_]|$)/i.test(code);
                            });
                            return matching.sort((a, b) => this.voiceScore(b) - this.voiceScore(a))[0] || null;
                        },
                        voiceScore(voice) {
                            const name = String(voice.name || '').toLowerCase();
                            const code = String(voice.lang || '').toLowerCase();
                            let score = voice.localService ? 3 : 0;
                            if (name.includes('natural') || name.includes('neural')) {
                                score += 6;
                            }
                            if (name.includes('zira') || name.includes('aria') || name.includes('jenny') || name.includes('guy') || name.includes('asad') || name.includes('mark')) {
                                score += 4;
                            }
                            if (code === 'ur-pk' || code === 'en-us') {
                                score += 3;
                            }
                            if (name.includes('david') || name.includes('espeak') || name.includes('compact')) {
                                score -= 6;
                            }
                            return score;
                        },
                        finishSpeaking() {
                            const thenListen = this.thenListen;
                            this.speakingId = null;
                            this.thenListen = false;
                            this.currentUtterance = null;
                            if (this.voiceNote === 'Speaking…') {
                                this.voiceNote = '';
                            }
                            if (thenListen && this.talking && !this.sending) {
                                setTimeout(() => this.beginListen(), 300);
                            }
                        },
                        stopSpeak() {
                            this.speakToken += 1;
                            this.thenListen = false;
                            this.currentUtterance = null;
                            if (this.speakTimer) {
                                clearTimeout(this.speakTimer);
                                this.speakTimer = null;
                            }
                            if (window.speechSynthesis) {
                                window.speechSynthesis.cancel();
                            }
                            this.speakingId = null;
                        },
                        stopVoice() {
                            this.micToken += 1;
                            this.openingMic = false;
                            this.listening = false;
                            if (this.meterTimer) {
                                clearInterval(this.meterTimer);
                                this.meterTimer = null;
                            }
                            const recorder = this.recorder;
                            this.recorder = null;
                            if (recorder && recorder.state === 'recording') {
                                try {
                                    recorder.stop();
                                } catch (error) {
                                    this.listening = false;
                                }
                            } else {
                                this.stream?.getTracks().forEach((track) => track.stop());
                                this.stream = null;
                                if (this.audioContext) {
                                    this.audioContext.close().catch(() => {});
                                    this.audioContext = null;
                                }
                                this.listening = false;
                            }
                        },
                    }));
                });
            </script>
            <form id="agent-reset" method="POST" action="{{ route('agent.reset') }}" class="hidden">
                @csrf
                <input type="hidden" name="mode" value="{{ $mode }}">
            </form>
        </div>
    </div>
</x-app-layout>
