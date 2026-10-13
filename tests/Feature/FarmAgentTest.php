<?php

namespace Tests\Feature;

use App\Models\AgentConversation;
use App\Models\Breed;
use App\Models\Goat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FarmAgentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'gemini.api_key' => 'test-key',
            'gemini.operations_model' => 'gemini-3.1-flash-lite',
            'gemini.research_model' => 'gemini-flash-latest',
            'gemini.timeout' => 5,
        ]);
        $this->user = User::factory()->create();
        $breed = Breed::create(['name' => 'Beetal', 'description' => 'Dairy']);
        Goat::create([
            'tag_id' => 'MG-7',
            'breed_id' => $breed->id,
            'gender' => 'female',
            'status' => 'breeder',
        ]);
    }

    public function test_a_reply_can_be_played_aloud(): void
    {
        $conversation = AgentConversation::create([
            'user_id' => $this->user->id,
            'mode' => 'operational',
            'title' => 'Herd',
        ]);
        $conversation->messages()->create([
            'role' => 'model',
            'content' => 'Six goats are in the herd.',
        ]);

        $this->actingAs($this->user)
            ->get(route('agent.index'))
            ->assertOk()
            ->assertSee('Listen')
            ->assertSee('speechSynthesis', false);
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get(route('agent.index'))->assertRedirect(route('login'));
    }

    public function test_page_renders_both_modes(): void
    {
        $this->actingAs($this->user)
            ->get(route('agent.index'))
            ->assertOk()
            ->assertSee('Operations')
            ->assertSee('Research')
            ->assertSee('Talk')
            ->assertSee('Dictate')
            ->assertSee('getUserMedia', false)
            ->assertDontSee('webkitSpeechRecognition', false)
            ->assertSee('English or Urdu')
            ->assertSee('ur-PK', false)
            ->assertDontSee('hi-IN', false)
            ->assertSee('agent\/messages', false)
            ->assertDontSee('requestSubmit', false);

        $this->actingAs($this->user)
            ->get(route('agent.index', ['mode' => 'research']))
            ->assertOk()
            ->assertSee('does not open the farm records');
    }

    public function test_operations_message_runs_a_tool_and_stores_the_reply(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'candidates' => [[
                        'content' => [
                            'role' => 'model',
                            'parts' => [[
                                'functionCall' => ['name' => 'search_goats', 'args' => ['query' => 'MG-7']],
                            ]],
                        ],
                    ]],
                ], 200)
                ->push([
                    'candidates' => [[
                        'content' => [
                            'role' => 'model',
                            'parts' => [['text' => 'MG-7 is in the herd.']],
                        ],
                    ]],
                ], 200),
        ]);

        $this->actingAs($this->user)
            ->post(route('agent.messages.store'), [
                'mode' => 'operational',
                'message' => 'Where is MG-7?',
            ])
            ->assertRedirect(route('agent.index', ['mode' => 'operational']))
            ->assertSessionHas('speak', true);

        $this->get(route('agent.index', ['mode' => 'operational']))
            ->assertOk()
            ->assertSee('MG-7 is in the herd.')
            ->assertSee('1 goats matched.');

        Http::assertSent(function ($request) {
            $tools = $request['tools'][0] ?? [];

            return isset($tools['functionDeclarations']) && ! isset($tools['googleSearch']);
        });
    }

    public function test_operations_can_record_a_weight(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'candidates' => [[
                        'content' => [
                            'role' => 'model',
                            'parts' => [[
                                'functionCall' => [
                                    'name' => 'record_weight',
                                    'args' => ['tag' => 'MG-7', 'weight_kg' => 28],
                                ],
                            ]],
                        ],
                    ]],
                ], 200)
                ->push([
                    'candidates' => [[
                        'content' => [
                            'role' => 'model',
                            'parts' => [['text' => 'Saved 28 kg for MG-7.']],
                        ],
                    ]],
                ], 200),
        ]);

        $this->actingAs($this->user)
            ->post(route('agent.messages.store'), [
                'mode' => 'operational',
                'message' => 'Record 28 kg for MG-7 today.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('weight_logs', ['weight_kg' => 28]);
    }

    public function test_research_uses_search_and_shows_the_source(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'role' => 'model',
                        'parts' => [['text' => 'Kids need browse plus a measured concentrate.']],
                    ],
                    'groundingMetadata' => [
                        'groundingChunks' => [[
                            'web' => ['uri' => 'https://example.edu/goat-feeding', 'title' => 'Goat feeding guide'],
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $this->actingAs($this->user)
            ->post(route('agent.messages.store'), [
                'mode' => 'research',
                'message' => 'What should a growing kid eat?',
            ])
            ->assertRedirect(route('agent.index', ['mode' => 'research']));

        $this->get(route('agent.index', ['mode' => 'research']))
            ->assertSee('Goat feeding guide')
            ->assertSee('goat-feeding', false);

        Http::assertSent(function ($request) {
            $tools = $request['tools'][0] ?? [];

            return isset($tools['googleSearch']) && ! isset($tools['functionDeclarations']);
        });
    }

    public function test_json_reply_returns_both_turns_without_a_redirect(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'role' => 'model',
                        'parts' => [['text' => 'I am here.']],
                    ],
                ]],
            ], 200),
        ]);

        $this->actingAs($this->user)
            ->postJson(route('agent.messages.store'), [
                'mode' => 'operational',
                'message' => 'Hello',
            ])
            ->assertOk()
            ->assertJsonPath('user.content', 'Hello')
            ->assertJsonPath('user.role', 'user')
            ->assertJsonPath('reply.content', 'I am here.')
            ->assertJsonPath('reply.role', 'model');

        $this->assertDatabaseHas('agent_messages', ['content' => 'I am here.']);

        Http::assertSent(function ($request) {
            $text = $request['systemInstruction']['parts'][0]['text'] ?? '';

            return str_contains($text, 'Reply only in English.');
        });
    }

    public function test_urdu_replies_are_requested_in_pakistani_urdu(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'role' => 'model',
                        'parts' => [['text' => 'ریوڑ میں چھ بکریاں ہیں۔']],
                    ],
                ]],
            ], 200),
        ]);

        $this->actingAs($this->user)
            ->postJson(route('agent.messages.store'), [
                'mode' => 'operational',
                'message' => 'بکریاں کتنی ہیں؟',
            ])
            ->assertOk()
            ->assertJsonPath('reply.content', 'ریوڑ میں چھ بکریاں ہیں۔');

        Http::assertSent(function ($request) {
            $text = $request['systemInstruction']['parts'][0]['text'] ?? '';

            return str_contains($text, 'Urdu as spoken in Pakistan')
                && str_contains($text, 'Do not use Hindi');
        });
    }

    public function test_english_spoken_into_urdu_letters_stays_english(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'role' => 'model',
                        'parts' => [['text' => 'I am fine.']],
                    ],
                ]],
            ], 200),
        ]);

        $this->actingAs($this->user)
            ->postJson(route('agent.messages.store'), [
                'mode' => 'operational',
                'message' => 'ہیلو ایجنٹ ہاؤ ار یو',
            ])
            ->assertOk()
            ->assertJsonPath('reply.content', 'I am fine.');

        Http::assertSent(function ($request) {
            $text = $request['systemInstruction']['parts'][0]['text'] ?? '';

            return str_contains($text, 'Reply only in English.')
                && str_contains($text, 'written in Urdu letters');
        });
    }

    public function test_spoken_audio_is_transcribed_before_the_reply(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'candidates' => [[
                        'content' => [
                            'role' => 'model',
                            'parts' => [['text' => 'How many goats are there?']],
                        ],
                    ]],
                ], 200)
                ->push([
                    'candidates' => [[
                        'content' => [
                            'role' => 'model',
                            'parts' => [['text' => 'There are 6 goats.']],
                        ],
                    ]],
                ], 200),
        ]);

        $this->actingAs($this->user)
            ->post(route('agent.messages.store'), [
                'mode' => 'operational',
                'audio' => UploadedFile::fake()->createWithContent('speech.webm', 'fake-audio')->mimeType('audio/webm'),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('user.content', 'How many goats are there?')
            ->assertJsonPath('reply.content', 'There are 6 goats.');

        Http::assertSent(function ($request) {
            $parts = $request['contents'][0]['parts'] ?? [];

            return isset($parts[0]['inlineData']['mimeType'])
                && ! isset($request['tools']);
        });
    }

    public function test_missing_key_is_explained(): void
    {
        config(['gemini.api_key' => null]);

        $this->actingAs($this->user)
            ->from(route('agent.index'))
            ->post(route('agent.messages.store'), [
                'mode' => 'operational',
                'message' => 'How many goats?',
            ])
            ->assertRedirect(route('agent.index', ['mode' => 'operational']))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('agent_messages', 0);
    }
}
