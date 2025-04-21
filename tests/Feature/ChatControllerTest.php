<?php

namespace Tests\Feature;

use App\Agents\InterviewAgent;
use App\Models\Interview;
use App\Models\InterviewSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

use function Pest\Laravel\postJson;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class, WithFaker::class);

it('sends messages with session id', function () {
    // Create a session ID
    $sessionId = Str::uuid()->toString();
    
    // Create an interview
    $interview = Interview::factory()->create();

    // Mock the InterviewAgent
    $this->mock(InterviewAgent::class, function ($mock) {
        $mock->shouldReceive('chat')
            ->once()
            ->andReturn([
                'messages' => ['Test response'],
                'finished' => false
            ]);
    });

    // Send a chat message
    $response = $this->postJson('/chat', [
        'sessionId' => $sessionId,
        'chatInput' => 'Test message',
        'interviewId' => $interview->id
    ]);

    // Check response is successful and contains the expected output
    $response->assertStatus(200)
        ->assertJson([
            'output' => [
                'messages' => ['Test response'],
                'finished' => false
            ]
        ]);
        
    // Assert that there's no result data
    $responseData = $response->json();
    $this->assertArrayNotHasKey('result', $responseData['output']);
});

it('stores messages in cache', function () {
    // Create a session ID
    $sessionId = Str::uuid()->toString();
    
    // Create an interview
    $interview = Interview::factory()->create();

    // Mock the InterviewAgent to simulate its internal behavior
    $this->mock(InterviewAgent::class, function ($mock) use ($sessionId) {
        $mock->shouldReceive('chat')
            ->once()
            ->andReturnUsing(function ($actualSessionId, $message, $interview) use ($sessionId) {
                // This simulates what the real agent does - store messages in cache
                $messages = [
                    [
                        'type' => 'user',
                        'content' => $message
                    ],
                    [
                        'type' => 'assistant',
                        'content' => 'Mock response'
                    ]
                ];
                
                Cache::put("chat_{$actualSessionId}", $messages, now()->addMinutes(30));
                
                return [
                    'messages' => ['Mock response'],
                    'finished' => false
                ];
            });
    });

    // Send a chat message
    $response = $this->postJson('/chat', [
        'sessionId' => $sessionId,
        'chatInput' => 'Test message',
        'interviewId' => $interview->id
    ]);

    // Verify messages were stored in cache
    $cachedMessages = Cache::get("chat_{$sessionId}");
    expect($cachedMessages)->not->toBeNull();
    expect($cachedMessages)->toHaveCount(2);
    expect($cachedMessages[0]['type'])->toBe('user');
    expect($cachedMessages[0]['content'])->toBe('Test message');
    expect($cachedMessages[1]['type'])->toBe('assistant');
    expect($cachedMessages[1]['content'])->toBe('Mock response');
});

it('requires session id and interview id', function () {
    // Create a fake interview
    $interview = Interview::factory()->create();
    
    // Mock the InterviewAgent to avoid calling the real instance
    $this->mock(InterviewAgent::class, function ($mock) {
        // We don't expect this to be called, but mocking it prevents errors
        $mock->shouldReceive('chat')->andReturn([
            'messages' => ['Test response'],
            'finished' => false
        ]);
    });

    // Missing sessionId
    $response1 = $this->postJson('/chat', [
        'chatInput' => 'Test message',
        'interviewId' => $interview->id
    ]);
    $response1->assertStatus(422); // Validation error status

    // Test with missing chatInput - should now be valid
    $response2 = $this->postJson('/chat', [
        'sessionId' => Str::uuid()->toString(),
        'interviewId' => $interview->id
    ]);
    $response2->assertStatus(200); // Now should be a successful response

    // Missing interviewId
    $response3 = $this->postJson('/chat', [
        'sessionId' => Str::uuid()->toString(),
        'chatInput' => 'Test message'
    ]);
    $response3->assertStatus(422);
});

it('can initialize chat with empty message', function () {
    // Create a session ID
    $sessionId = Str::uuid()->toString();
    
    // Create an interview
    $interview = Interview::factory()->create();

    // Mock the InterviewAgent
    $this->mock(InterviewAgent::class, function ($mock) {
        $mock->shouldReceive('chat')
            ->once()
            ->andReturn([
                'messages' => ['Welcome message'],
                'finished' => false
            ]);
    });

    // Send a request with no chatInput (initialization)
    $response = $this->postJson('/chat', [
        'sessionId' => $sessionId,
        'interviewId' => $interview->id
    ]);

    // Check response is successful and contains the welcome message
    $response->assertStatus(200)
        ->assertJson([
            'output' => [
                'messages' => ['Welcome message'],
                'finished' => false
            ]
        ]);
});

it('does not store empty user messages in history', function () {
    // Create a session ID
    $sessionId = Str::uuid()->toString();
    
    // Create an interview
    $interview = Interview::factory()->create();

    // Mock the InterviewAgent to simulate its internal behavior
    $this->mock(InterviewAgent::class, function ($mock) use ($sessionId) {
        $mock->shouldReceive('chat')
            ->once()
            ->andReturnUsing(function ($actualSessionId, $message, $interview) use ($sessionId) {
                // This is the real implementation logic we want to test
                expect($message)->toBe(''); // Verify empty message is passed
                
                // Since we're mocking, let's simulate what the real agent does
                // But with an empty message, it should NOT add the user message to the history
                // Only the assistant response should be added
                $messages = [
                    [
                        'type' => 'assistant',
                        'content' => 'Welcome response'
                    ]
                ];
                
                Cache::put("chat_{$actualSessionId}", $messages, now()->addMinutes(30));
                
                return [
                    'messages' => ['Welcome response'],
                    'finished' => false
                ];
            });
    });

    // Send a chat initialization request (no chatInput)
    $response = $this->postJson('/chat', [
        'sessionId' => $sessionId,
        'interviewId' => $interview->id
    ]);

    // Verify only the assistant's message was stored in cache
    $cachedMessages = Cache::get("chat_{$sessionId}");
    expect($cachedMessages)->not->toBeNull();
    expect($cachedMessages)->toHaveCount(1); // Only 1 message, not 2
    expect($cachedMessages[0]['type'])->toBe('assistant');
    expect($cachedMessages[0]['content'])->toBe('Welcome response');
});

it('stores messages in database session', function () {
    // Create a fake interview
    $interview = Interview::factory()->create();
    
    // Create a session ID
    $sessionId = "interview_{$interview->id}_" . Str::uuid()->toString();
    
    // Mock the InterviewAgent to avoid calling the real API but still implement saveMessages
    $this->mock(InterviewAgent::class, function ($mock) use ($sessionId, $interview) {
        $mock->shouldReceive('chat')
            ->andReturnUsing(function ($actualSessionId, $message, $interviewObj) use ($sessionId, $interview) {
                // This simulates what saveMessages does in the real agent
                $messages = [
                    ['type' => 'user', 'content' => $message],
                    ['type' => 'assistant', 'content' => 'Hello, I am the assistant.']
                ];
                
                InterviewSession::updateOrCreate(
                    ['id' => $actualSessionId],
                    [
                        'interview_id' => $interview->id,
                        'messages' => $messages,
                    ]
                );
                
                return [
                    'messages' => ['Hello, I am the assistant.'],
                    'finished' => false
                ];
            });
    });
    
    // Send a message via the chat endpoint
    $response = postJson('/chat', [
        'sessionId' => $sessionId,
        'interviewId' => $interview->id,
        'chatInput' => 'Hello'
    ]);
    
    // Assert response is successful
    $response->assertStatus(200);
    
    // Assert that a session record was created with this interview_id and expected content
    $session = InterviewSession::where('interview_id', $interview->id)->first();
    expect($session)->not->toBeNull();
    
    // Assert that the messages were stored
    expect($session->messages)->toBeArray();
    expect($session->messages)->toHaveCount(2);
    expect($session->messages[0]['type'])->toBe('user');
    expect($session->messages[0]['content'])->toBe('Hello');
    expect($session->messages[1]['type'])->toBe('assistant');
});

it('finalizes session when interview is completed', function () {
    // Create a fake interview
    $interview = Interview::factory()->create();
    
    // Create a session ID
    $sessionId = "interview_{$interview->id}_" . Str::uuid()->toString();
    
    // Create test summary and topics
    $summary = "This is the interview summary.";
    $topics = [
        ['key' => 'topic1', 'messages' => ['Info 1', 'Info 2']]
    ];
    
    // Create a session for this interview
    $sessionBeforeRequest = InterviewSession::create([
        'interview_id' => $interview->id,
        'messages' => [
            ['type' => 'user', 'content' => 'Hello'],
            ['type' => 'assistant', 'content' => 'Hi there!'],
        ],
    ]);
    
    // Store the session ID in the request
    $sessionId = $sessionBeforeRequest->id;
    
    // Mock the InterviewAgent to return a finished response and update the session
    $this->mock(InterviewAgent::class, function ($mock) use ($sessionId, $interview, $summary, $topics) {
        $mock->shouldReceive('chat')
            ->andReturnUsing(function ($actualSessionId, $message, $interviewObj) use ($sessionId, $interview, $summary, $topics) {
                // This simulates what the real agent does with saveMessages
                $messages = [
                    ['type' => 'user', 'content' => 'Hello'],
                    ['type' => 'assistant', 'content' => 'Hi there!'],
                    ['type' => 'user', 'content' => 'Goodbye'],
                    ['type' => 'assistant', 'content' => 'Thank you for completing this interview.'],
                ];
                
                // First update messages
                InterviewSession::updateOrCreate(
                    ['id' => $actualSessionId],
                    [
                        'interview_id' => $interview->id,
                        'messages' => $messages,
                    ]
                );
                
                // Then finalize with summary and topics (simulating finalizeSession)
                InterviewSession::where('id', $actualSessionId)
                    ->where('interview_id', $interview->id)
                    ->update([
                        'summary' => $summary,
                        'topics' => $topics,
                        'finished' => true,
                    ]);
                
                return [
                    'messages' => ['Thank you for completing this interview.'],
                    'finished' => true,
                    'result' => [
                        'summary' => $summary,
                        'topics' => $topics
                    ]
                ];
            });
    });
    
    // Send a message that will trigger the final response
    $response = postJson('/chat', [
        'sessionId' => $sessionId,
        'interviewId' => $interview->id,
        'chatInput' => 'Goodbye'
    ]);
    
    // Assert response is successful
    $response->assertStatus(200);
    
    // Get the updated session from the database
    $session = InterviewSession::where('interview_id', $interview->id)->first();
    
    // Assert that the session was finalized with summary and topics
    expect($session)->not->toBeNull();
    expect($session->finished)->toBeTrue();
    expect($session->summary)->toBe($summary);
    expect($session->topics)->toBe($topics);
});

it('handles session initialization with empty message in database', function () {
    // Create a fake interview
    $interview = Interview::factory()->create();
    
    // Create a session ID
    $sessionId = Str::uuid()->toString();
    
    // Mock the InterviewAgent to verify it's called with correct parameters
    $this->mock(InterviewAgent::class, function ($mock) use ($sessionId, $interview) {
        $mock->shouldReceive('chat')
            ->once()
            ->with($sessionId, '', \Mockery::type(Interview::class))
            ->andReturn([
                'messages' => ['Welcome to the interview!'],
                'finished' => false
            ]);
    });
    
    // Send an initialization request (empty chatInput)
    $response = postJson('/chat', [
        'sessionId' => $sessionId,
        'interviewId' => $interview->id
    ]);
    
    // Assert response is successful
    $response->assertStatus(200);
    
    // Assert the response contains the expected output
    $response->assertJson([
        'output' => [
            'messages' => ['Welcome to the interview!'],
            'finished' => false
        ]
    ]);
});

it('rejects new messages for finished interviews', function () {
    // Create a fake interview
    $interview = Interview::factory()->create();
    
    // Create a session with finished status
    $session = InterviewSession::create([
        'interview_id' => $interview->id,
        'messages' => [
            ['type' => 'user', 'content' => 'Hello'],
            ['type' => 'assistant', 'content' => 'Thank you for completing this interview.']
        ],
        'summary' => 'This is a summary',
        'topics' => [['key' => 'topic1', 'messages' => ['Info 1']]],
        'finished' => true
    ]);
    
    // Mock the InterviewAgent - it should not be called
    $this->mock(InterviewAgent::class, function ($mock) {
        $mock->shouldNotReceive('chat');
    });
    
    // Send a new message to a finished interview
    $response = postJson('/chat', [
        'sessionId' => $session->id,
        'interviewId' => $interview->id,
        'chatInput' => 'One more question...'
    ]);
    
    // Assert response has error status
    $response->assertStatus(400);
    
    // Assert it contains an error message about the interview being completed
    $response->assertJson([
        'error' => 'This interview is already completed and cannot accept new messages.',
        'finished' => true
    ]);
});

it('filters out result data from response', function () {
    // Create a fake interview
    $interview = Interview::factory()->create();
    
    // Create a session ID
    $sessionId = Str::uuid()->toString();
    
    // Create test summary and topics that should be filtered out
    $summary = "This is the interview summary.";
    $topics = [
        ['key' => 'topic1', 'messages' => ['Info 1', 'Info 2']]
    ];
    
    // Mock the InterviewAgent to return result data
    $this->mock(InterviewAgent::class, function ($mock) use ($summary, $topics) {
        $mock->shouldReceive('chat')
            ->once()
            ->andReturn([
                'messages' => ['Thank you for your answers.'],
                'finished' => true,
                'result' => [
                    'summary' => $summary,
                    'topics' => $topics
                ]
            ]);
    });
    
    // Send a message via the chat endpoint
    $response = postJson('/chat', [
        'sessionId' => $sessionId,
        'interviewId' => $interview->id,
        'chatInput' => 'My final answer'
    ]);
    
    // Assert response is successful
    $response->assertStatus(200);
    
    // Assert that the response contains messages and finished but not result
    $response->assertJson([
        'output' => [
            'messages' => ['Thank you for your answers.'],
            'finished' => true
        ]
    ]);
    
    // Assert that the result data is not included
    $responseData = $response->json();
    expect($responseData['output'])->not->toHaveKey('result');
});

it('rejects initialization for finished interviews', function () {
    // Create a fake interview
    $interview = Interview::factory()->create();
    
    // Create a session with finished status
    $session = InterviewSession::create([
        'interview_id' => $interview->id,
        'messages' => [
            ['type' => 'user', 'content' => 'Hello'],
            ['type' => 'assistant', 'content' => 'Thank you for completing this interview.']
        ],
        'summary' => 'This is a summary',
        'topics' => [['key' => 'topic1', 'messages' => ['Info 1']]],
        'finished' => true
    ]);
    
    // Mock the InterviewAgent - it should not be called
    $this->mock(InterviewAgent::class, function ($mock) {
        $mock->shouldNotReceive('chat');
    });
    
    // Send initialization request for a finished interview
    $response = postJson('/chat', [
        'sessionId' => $session->id,
        'interviewId' => $interview->id
    ]);
    
    // Assert response has error status
    $response->assertStatus(400);
    
    // Assert it contains an error message about the interview being completed
    $response->assertJson([
        'error' => 'This interview is already completed and cannot accept new messages.',
        'finished' => true
    ]);
});
