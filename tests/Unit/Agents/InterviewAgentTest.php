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
