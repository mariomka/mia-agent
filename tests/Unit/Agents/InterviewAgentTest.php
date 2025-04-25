<?php

use App\Agents\InterviewAgent;
use App\Models\Interview;
use App\Models\InterviewSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Prism;
use Prism\Prism\Structured\Response as StructuredResponse;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

uses(RefreshDatabase::class);

test('chat method returns structured response', function () {
    $sessionId = Str::uuid()->toString();
    $userMessage = 'Hello, I\'m here for the interview';

    $interview = Interview::factory()->create();

    $response = [
        'messages' => ['Hello! I\'m Test Agent. Let\'s talk about Test Product.'],
        'finished' => false,
        'result' => [
            'summary' => null,
            'topics' => null
        ]
    ];
    $fakeResponse = new StructuredResponse(
        steps: collect([]),
        responseMessages: collect([]),
        text: json_encode($response),
        structured: $response,
        finishReason: FinishReason::Stop,
        usage: new Usage(10, 20),
        meta: new Meta('fake-1', 'fake-model'),
        additionalContent: []
    );

    $fake = Prism::fake([$fakeResponse]);

    $agent = new InterviewAgent();
    $result = $agent->chat($sessionId, $userMessage, $interview);

    expect($result)->toBeArray()
        ->and($result['messages'])->toBeArray()
        ->and($result['messages'][0])->toBe('Hello! I\'m Test Agent. Let\'s talk about Test Product.')
        ->and($result['finished'])->toBeFalse();

    $fake->assertCallCount(1);
});

test('chat method handles interview completion', function () {
    $sessionId = Str::uuid()->toString();
    $userMessage = 'Hello, I\'m here for the interview';

    $interview = Interview::factory()->create();

    $response = [
        'messages' => ['Thank you for completing the interview!'],
        'finished' => true,
        'result' => [
            'summary' => 'This was a great interview about Test Product',
            'topics' => [
                [
                    'key' => 'topic1',
                    'messages' => ['User likes feature X', 'User dislikes feature Y']
                ],
                [
                    'key' => 'topic2',
                    'messages' => ['User suggests improvement Z']
                ]
            ]
        ]
    ];
    $fakeResponse = new StructuredResponse(
        steps: collect([]),
        responseMessages: collect([]),
        text: json_encode($response),
        structured: $response,
        finishReason: FinishReason::Stop,
        usage: new Usage(10, 20),
        meta: new Meta('fake-1', 'fake-model'),
        additionalContent: []
    );

    $fake = Prism::fake([$fakeResponse]);

    $agent = new InterviewAgent();
    $result = $agent->chat($sessionId, $userMessage, $interview);

    expect($result)->toBeArray()
        ->and($result['messages'][0])->toBe('Thank you for completing the interview!')
        ->and($result['finished'])->toBeTrue()
        ->and($result['result']['summary'])->toBe('This was a great interview about Test Product')
        ->and($result['result']['topics'])->toHaveCount(2);

    $fake->assertCallCount(1);
});

test('chat method calculates cost correctly', function () {
    Config::set('prism.pricing.openai.gpt-4.1-mini.input', 1); // $1 per million tokens
    Config::set('prism.pricing.openai.gpt-4.1-mini.output', 2); // $2 per million tokens

    $session = InterviewSession::factory()->create();
    $userMessage = 'Test message';

    $interview = Interview::factory()->create();

    // Create fake structured response
    $fakeResponse = new StructuredResponse(
        steps: collect([]),
        responseMessages: collect([]),
        text: json_encode([]),
        structured: [],
        finishReason: FinishReason::Stop,
        usage: new Usage(1_000, 500), // 1000 input tokens, 500 output tokens
        meta: new Meta('fake-1', 'fake-model'),
        additionalContent: []
    );

    Prism::fake([$fakeResponse]);

    $agent = new InterviewAgent();
    $agent->chat($session->id, $userMessage, $interview);

    $session->refresh();

    expect((float) $session->cost)->toBe((1_000 / 1_000_000 * 1) + (500 / 1_000_000 * 2));
    expect($session->input_tokens)->toBe(1000);
    expect($session->output_tokens)->toBe(500);
});

test('chat method accumulates tokens and cost across multiple calls', function () {
    Config::set('prism.pricing.openai.gpt-4.1-mini.input', 1); // $1 per million tokens
    Config::set('prism.pricing.openai.gpt-4.1-mini.output', 2); // $2 per million tokens

    $session = InterviewSession::factory()->create();
    $interview = Interview::factory()->create();

    // First call - 1000 input tokens, 500 output tokens
    $fakeResponse1 = new StructuredResponse(
        steps: collect([]),
        responseMessages: collect([]),
        text: json_encode([]),
        structured: [],
        finishReason: FinishReason::Stop,
        usage: new Usage(1_000, 500),
        meta: new Meta('fake-1', 'fake-model'),
        additionalContent: []
    );

    // Second call - 800 input tokens, 400 output tokens
    $fakeResponse2 = new StructuredResponse(
        steps: collect([]),
        responseMessages: collect([]),
        text: json_encode([]),
        structured: [],
        finishReason: FinishReason::Stop,
        usage: new Usage(800, 400),
        meta: new Meta('fake-2', 'fake-model'),
        additionalContent: []
    );

    Prism::fake([$fakeResponse1, $fakeResponse2]);

    $agent = new InterviewAgent();

    // First chat call
    $agent->chat($session->id, 'First message', $interview);
    $session->refresh();

    expect((float) $session->cost)->toBe((1_000 / 1_000_000 * 1) + (500 / 1_000_000 * 2))
        ->and($session->input_tokens)->toBe(1000)
        ->and($session->output_tokens)->toBe(500);

    // Second chat call
    $agent->chat($session->id, 'Second message', $interview);
    $session->refresh();

    // Total cost should be sum of both calls
    $expectedTotalCost = ((1_000 + 800) / 1_000_000 * 1) + ((500 + 400) / 1_000_000 * 2);
    expect((float) $session->cost)->toBe($expectedTotalCost)
        ->and($session->input_tokens)->toBe(1800) // 1000 + 800
        ->and($session->output_tokens)->toBe(900); // 500 + 400
});

test('chat method updates session with messages and result when completed', function () {
    $session = InterviewSession::factory()->create();
    $interview = Interview::factory()->create();
    $userMessage = 'Final message';

    $summary = "This is a summary of the interview";
    $topics = [
        [
            'key' => 'topic1',
            'messages' => ['User mentioned X', 'User prefers Y']
        ]
    ];

    $response = [
        'messages' => ['Thank you for completing this interview.'],
        'finished' => true,
        'result' => [
            'summary' => $summary,
            'topics' => $topics
        ]
    ];

    $fakeResponse = new StructuredResponse(
        steps: collect([]),
        responseMessages: collect([]),
        text: json_encode($response),
        structured: $response,
        finishReason: FinishReason::Stop,
        usage: new Usage(500, 300),
        meta: new Meta('fake-1', 'fake-model'),
        additionalContent: []
    );

    Prism::fake([$fakeResponse]);

    $agent = new InterviewAgent();
    $result = $agent->chat($session->id, $userMessage, $interview);

    $session->refresh();

    expect($result['finished'])->toBeTrue();
    expect($result['result']['summary'])->toBe($summary);
    expect($result['result']['topics'])->toBe($topics);

    // Verify the session was updated
    expect($session->finished)->toBeTrue();
    expect($session->summary)->toBe($summary);
    expect($session->topics)->toBe($topics);
});

test('chat method stores messages in session', function () {
    $session = InterviewSession::factory()->create([
        'messages' => [
            ['type' => 'user', 'content' => 'Previous message'],
            ['type' => 'assistant', 'content' => 'Previous response']
        ]
    ]);
    $interview = Interview::factory()->create();
    $userMessage = 'New message';

    $response = [
        'messages' => ['This is my response'],
        'finished' => false,
        'result' => [
            'summary' => null,
            'topics' => null
        ]
    ];

    $fakeResponse = new StructuredResponse(
        steps: collect([]),
        responseMessages: collect([]),
        text: json_encode($response),
        structured: $response,
        finishReason: FinishReason::Stop,
        usage: new Usage(300, 200),
        meta: new Meta('fake-1', 'fake-model'),
        additionalContent: []
    );

    Prism::fake([$fakeResponse]);

    $agent = new InterviewAgent();
    $agent->chat($session->id, $userMessage, $interview);

    $session->refresh();

    expect($session->messages)->toHaveCount(4);
    expect($session->messages[2]['type'])->toBe('user');
    expect($session->messages[2]['content'])->toBe('New message');
    expect($session->messages[3]['type'])->toBe('assistant');
    expect($session->messages[3]['content'])->toBe('This is my response');
});

test('chat method does not add empty user messages to history', function () {
    $session = InterviewSession::factory()->create([
        'messages' => []
    ]);
    $interview = Interview::factory()->create();
    $userMessage = '';

    $response = [
        'messages' => ['Welcome to the interview!'],
        'finished' => false,
        'result' => [
            'summary' => null,
            'topics' => null
        ]
    ];

    $fakeResponse = new StructuredResponse(
        steps: collect([]),
        responseMessages: collect([]),
        text: json_encode($response),
        structured: $response,
        finishReason: FinishReason::Stop,
        usage: new Usage(200, 100),
        meta: new Meta('fake-1', 'fake-model'),
        additionalContent: []
    );

    Prism::fake([$fakeResponse]);

    $agent = new InterviewAgent();
    $agent->chat($session->id, $userMessage, $interview);

    $session->refresh();

    expect($session->messages)->toHaveCount(1);
    expect($session->messages[0]['type'])->toBe('assistant');
    expect($session->messages[0]['content'])->toBe('Welcome to the interview!');
});

test('chat method limits output to maximum of two messages per turn', function () {
    $session = InterviewSession::factory()->create([
        'messages' => []
    ]);
    $interview = Interview::factory()->create();
    $userMessage = 'Hello';

    // Create a response with more than 2 messages
    $response = [
        'messages' => [
            'Message 1',
            'Message 2',
            'Message 3',
            'Message 4'
        ],
        'finished' => false,
        'result' => [
            'summary' => null,
            'topics' => null
        ]
    ];

    $fakeResponse = new StructuredResponse(
        steps: collect([]),
        responseMessages: collect([]),
        text: json_encode($response),
        structured: $response,
        finishReason: FinishReason::Stop,
        usage: new Usage(200, 100),
        meta: new Meta('fake-1', 'fake-model'),
        additionalContent: []
    );

    Prism::fake([$fakeResponse]);

    $agent = new InterviewAgent();
    $result = $agent->chat($session->id, $userMessage, $interview);

    $session->refresh();

    // Verify result only contains the first two messages
    expect($result['messages'])->toHaveCount(2);
    expect($result['messages'][0])->toBe('Message 1');
    expect($result['messages'][1])->toBe('Message 2');

    // Verify session messages also only includes the first two assistant messages
    expect($session->messages)->toHaveCount(3); // 1 user message + 2 assistant messages
    expect($session->messages[0]['type'])->toBe('user');
    expect($session->messages[0]['content'])->toBe('Hello');
    expect($session->messages[1]['type'])->toBe('assistant');
    expect($session->messages[1]['content'])->toBe('Message 1');
    expect($session->messages[2]['type'])->toBe('assistant');
    expect($session->messages[2]['content'])->toBe('Message 2');
});

test('chat method uses goodbye message for final interaction if defined', function () {
    // Create a session first
    $session = InterviewSession::factory()->create();
    $userMessage = 'Final message';

    // Create interview with a goodbye message
    $goodbyeMessage = 'This is a custom goodbye message!';
    $interview = Interview::factory()->create([
        'goodbye_message' => $goodbyeMessage
    ]);

    $summary = "This is a summary of the interview";
    $topics = [
        [
            'key' => 'topic1',
            'messages' => ['User mentioned X', 'User prefers Y']
        ]
    ];

    // LLM response indicating the interview is finished
    $response = [
        'messages' => ['This is the original final message.'],
        'finished' => true,
        'result' => [
            'summary' => $summary,
            'topics' => $topics
        ]
    ];

    $fakeResponse = new StructuredResponse(
        steps: collect([]),
        responseMessages: collect([]),
        text: json_encode($response),
        structured: $response,
        finishReason: FinishReason::Stop,
        usage: new Usage(500, 300),
        meta: new Meta('fake-1', 'fake-model'),
        additionalContent: []
    );

    Prism::fake([$fakeResponse]);

    $agent = new InterviewAgent();
    $result = $agent->chat($session->id, $userMessage, $interview);

    $session->refresh();

    // Verify the goodbye message replaces the original final message
    expect($result['messages'])->toHaveCount(1);
    expect($result['messages'][0])->toBe($goodbyeMessage);
    expect($result['finished'])->toBeTrue();

    // Verify the session has the goodbye message
    $messages = $session->messages;
    expect($messages)->toBeArray();

    // Get the last message from the array
    $lastIndex = count($messages) - 1;
    expect($lastIndex)->toBeGreaterThanOrEqual(0);
    expect($messages[$lastIndex]['type'])->toBe('assistant');
    expect($messages[$lastIndex]['content'])->toBe($goodbyeMessage);

    // Verify the session was finalized
    expect($session->finished)->toBeTrue();
    expect($session->summary)->toBe($summary);
    expect($session->topics)->toBe($topics);
});

test('chat method uses custom welcome message for first interaction', closure: function () {
    $session = InterviewSession::factory()->create();
    $userMessage = ''; // Empty message for initialization

    // Create interview with a welcome message
    $welcomeMessage = 'This is a custom welcome message!';
    $interview = Interview::factory()->create([
        'welcome_message' => $welcomeMessage
    ]);

    $assistantMessage = 'This response should follow the welcome message.';
    $fakeResponse = new StructuredResponse(
        steps: collect([]),
        responseMessages: collect([]),
        text: json_encode([
            'messages' => [$assistantMessage],
            'finished' => false
        ]),
        structured: [
            'messages' => [$assistantMessage],
            'finished' => false
        ],
        finishReason: FinishReason::Stop,
        usage: new Usage(100, 50),
        meta: new Meta('fake-1', 'fake-model'),
        additionalContent: []
    );

    // Fake the LLM response
    Prism::fake([$fakeResponse]);

    // Create the agent and call chat
    $agent = new InterviewAgent();
    $result = $agent->chat($session->id, $userMessage, $interview);

    // Verify the welcome message is returned directly without calling the LLM
    expect($result)->toBeArray()
        ->and($result['messages'])->toHaveCount(2)
        ->and($result['messages'][0])->toBe($welcomeMessage)
        ->and($result['messages'][1])->toBe($assistantMessage);

    // Verify a session was created with the welcome message
    $session = InterviewSession::where('id', $session->id)->first();
    expect($session)->not->toBeNull();
    expect($session->messages)->toHaveCount(2);
    expect($session->messages[0]['type'])->toBe('assistant');
    expect($session->messages[0]['content'])->toBe($welcomeMessage);
    expect($session->messages)->toHaveCount(2);
    expect($session->messages[1]['type'])->toBe('assistant');
    expect($session->messages[1]['content'])->toBe($assistantMessage);
});

test('chat method uses custom goodbye message for last interaction', closure: function () {
    $session = InterviewSession::factory()->create();
    $userMessage = ''; // Empty message for initialization

    // Create interview with a welcome message
    $goodbyeMessage = 'This is a custom goodbye message!';
    $interview = Interview::factory()->create([
        'goodbye_message' => $goodbyeMessage
    ]);

    $fakeResponse = new StructuredResponse(
        steps: collect([]),
        responseMessages: collect([]),
        text: json_encode([
            'messages' => ['Not used message.'],
            'finished' => true
        ]),
        structured: [
            'messages' => ['Not used message.'],
            'finished' => true
        ],
        finishReason: FinishReason::Stop,
        usage: new Usage(100, 50),
        meta: new Meta('fake-1', 'fake-model'),
        additionalContent: []
    );

    // Fake the LLM response
    Prism::fake([$fakeResponse]);

    // Create the agent and call chat
    $agent = new InterviewAgent();
    $result = $agent->chat($session->id, $userMessage, $interview);

    // Verify the welcome message is returned directly without calling the LLM
    expect($result)->toBeArray()
        ->and($result['messages'])->toHaveCount(1)
        ->and($result['messages'][0])->toBe($goodbyeMessage);

    // Verify a session was created with the welcome message
    $session = InterviewSession::where('id', $session->id)->first();
    expect($session)->not->toBeNull();
    expect($session->messages)->toHaveCount(1);
    expect($session->messages[0]['type'])->toBe('assistant');
    expect($session->messages[0]['content'])->toBe($goodbyeMessage);
});

test('turnsExhausted flag is set correctly when maximum turns are reached', function () {
    $session = InterviewSession::factory()->create([
        'messages' => [
            ['type' => 'user', 'content' => 'Message 1'],
            ['type' => 'assistant', 'content' => 'Response 1'],
            ['type' => 'user', 'content' => 'Message 2'],
            ['type' => 'assistant', 'content' => 'Response 2'],
            ['type' => 'user', 'content' => 'Message 3'],
            ['type' => 'assistant', 'content' => 'Response 3'],
        ]
    ]);

    $interview = Interview::factory()->create([
        'topics' => [
            ['key' => 'topic1', 'question' => 'Question 1', 'description' => 'Description 1']
        ]
    ]);

    $response = [
        'messages' => ['This is a response'],
        'finished' => false
    ];

    $fakeResponse = new StructuredResponse(
        steps: collect([]),
        responseMessages: collect([]),
        text: json_encode($response),
        structured: $response,
        finishReason: FinishReason::Stop,
        usage: new Usage(100, 50),
        meta: new Meta('fake-1', 'fake-model'),
        additionalContent: []
    );

    $fake = Prism::fake([$fakeResponse, $fakeResponse]);

    // Not exhausted
    $agent = new InterviewAgent();
    $agent->chat($session->id, 'New message', $interview);

    $fake->assertRequest(function (array $requests) {
        expect($requests[0]->systemPrompts()[0]->content)
            ->not
            ->toContain('The maximum number of turns has been reached.');
    });

    // Exhausted
    $session->update([
        'messages' => [
            ...$session->messages,
            ['type' => 'user', 'content' => 'Message 4'],
            ['type' => 'assistant', 'content' => 'Response 4'],
        ],
    ]);

    $agent = new InterviewAgent();
    $agent->chat($session->id, 'New message', $interview);

    $fake->assertRequest(function (array $requests) {
        expect($requests[1]->systemPrompts()[0]->content)
            ->toContain('The maximum number of turns has been reached.');
    });
});
