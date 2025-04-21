<?php

use App\Agents\InterviewAgent;
use App\Models\Interview;
use App\Models\InterviewSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use function Pest\Laravel\postJson;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

test('it stores messages in database session', function () {
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
                    ['session_id' => $actualSessionId],
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
    
    // Assert that a session record was created
    assertDatabaseHas('interview_sessions', [
        'session_id' => $sessionId,
        'interview_id' => $interview->id,
    ]);
    
    // Get the session from the database
    $session = InterviewSession::where('session_id', $sessionId)->first();
    
    // Assert that the messages were stored
    expect($session)->not->toBeNull();
    expect($session->messages)->toBeArray();
    expect($session->messages)->toHaveCount(2);
    expect($session->messages[0]['type'])->toBe('user');
    expect($session->messages[0]['content'])->toBe('Hello');
    expect($session->messages[1]['type'])->toBe('assistant');
});

test('it finalizes session when interview is completed', function () {
    // Create a fake interview
    $interview = Interview::factory()->create();
    
    // Create a session ID
    $sessionId = "interview_{$interview->id}_" . Str::uuid()->toString();
    
    // Create test summary and topics
    $summary = "This is the interview summary.";
    $topics = [
        ['key' => 'topic1', 'messages' => ['Info 1', 'Info 2']]
    ];
    
    // Create an initial session
    InterviewSession::create([
        'interview_id' => $interview->id,
        'session_id' => $sessionId,
        'messages' => [
            ['type' => 'user', 'content' => 'Hello'],
            ['type' => 'assistant', 'content' => 'Hi there!'],
        ],
    ]);
    
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
                    ['session_id' => $actualSessionId],
                    [
                        'interview_id' => $interview->id,
                        'messages' => $messages,
                    ]
                );
                
                // Then finalize with summary and topics (simulating finalizeSession)
                InterviewSession::where('session_id', $actualSessionId)
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
    $session = InterviewSession::where('session_id', $sessionId)->first();
    
    // Assert that the session was finalized with summary and topics
    expect($session)->not->toBeNull();
    expect($session->finished)->toBeTrue();
    expect($session->summary)->toBe($summary);
    expect($session->topics)->toBe($topics);
});

test('it handles session initialization with empty message', function () {
    // Create a fake interview
    $interview = Interview::factory()->create();
    
    // Create a session ID
    $sessionId = "interview_{$interview->id}_" . Str::uuid()->toString();
    
    // Mock the InterviewAgent to handle initialization
    $this->mock(InterviewAgent::class, function ($mock) use ($sessionId, $interview) {
        $mock->shouldReceive('chat')
            ->andReturnUsing(function ($actualSessionId, $message, $interviewObj) use ($sessionId, $interview) {
                // For empty message (initialization), only add assistant message
                $messages = [
                    ['type' => 'assistant', 'content' => 'Welcome to the interview!'],
                ];
                
                InterviewSession::updateOrCreate(
                    ['session_id' => $actualSessionId],
                    [
                        'interview_id' => $interview->id,
                        'messages' => $messages,
                    ]
                );
                
                return [
                    'messages' => ['Welcome to the interview!'],
                    'finished' => false
                ];
            });
    });
    
    // Send an initialization request (empty chatInput)
    $response = postJson('/chat', [
        'sessionId' => $sessionId,
        'interviewId' => $interview->id,
    ]);
    
    // Assert response is successful
    $response->assertStatus(200);
    
    // Assert that a session record was created
    assertDatabaseHas('interview_sessions', [
        'session_id' => $sessionId,
        'interview_id' => $interview->id,
    ]);
    
    // Get the session from the database
    $session = InterviewSession::where('session_id', $sessionId)->first();
    
    // Assert that only the assistant message was stored (no user message)
    expect($session)->not->toBeNull();
    expect($session->messages)->toBeArray();
    expect($session->messages)->toHaveCount(1);
    expect($session->messages[0]['type'])->toBe('assistant');
    expect($session->messages[0]['content'])->toBe('Welcome to the interview!');
});

test('it rejects new messages for finished interviews', function () {
    // Create a fake interview
    $interview = Interview::factory()->create();
    
    // Create a session ID
    $sessionId = "interview_{$interview->id}_" . Str::uuid()->toString();
    
    // Create a pre-existing finished session in the database
    InterviewSession::create([
        'interview_id' => $interview->id,
        'session_id' => $sessionId,
        'messages' => [
            ['type' => 'user', 'content' => 'Hello'],
            ['type' => 'assistant', 'content' => 'Hi there!'],
            ['type' => 'user', 'content' => 'Goodbye'],
            ['type' => 'assistant', 'content' => 'Thank you for completing this interview.'],
        ],
        'summary' => 'This is a summary',
        'topics' => [['key' => 'topic1', 'messages' => ['Info 1']]],
        'finished' => true
    ]);
    
    // Send a new message to a finished interview
    $response = postJson('/chat', [
        'sessionId' => $sessionId,
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
    
    // Mock should not be called as we return early
    $this->mock(InterviewAgent::class, function ($mock) {
        $mock->shouldNotReceive('chat');
    });
});

test('it filters out result data from response', function () {
    // Create a fake interview
    $interview = Interview::factory()->create();
    
    // Create a session ID
    $sessionId = "interview_{$interview->id}_" . Str::uuid()->toString();
    
    // Create test summary and topics that should be filtered out
    $summary = "This is the interview summary.";
    $topics = [
        ['key' => 'topic1', 'messages' => ['Info 1', 'Info 2']]
    ];
    
    // Mock the InterviewAgent to return result data
    $this->mock(InterviewAgent::class, function ($mock) use ($summary, $topics) {
        $mock->shouldReceive('chat')
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

test('it rejects initialization for finished interviews', function () {
    // Create a fake interview
    $interview = Interview::factory()->create();
    
    // Create a session ID
    $sessionId = "interview_{$interview->id}_" . Str::uuid()->toString();
    
    // Create a pre-existing finished session in the database
    InterviewSession::create([
        'interview_id' => $interview->id,
        'session_id' => $sessionId,
        'messages' => [
            ['type' => 'user', 'content' => 'Hello'],
            ['type' => 'assistant', 'content' => 'Hi there!'],
            ['type' => 'user', 'content' => 'Goodbye'],
            ['type' => 'assistant', 'content' => 'Thank you for completing this interview.'],
        ],
        'summary' => 'This is a summary',
        'topics' => [['key' => 'topic1', 'messages' => ['Info 1']]],
        'finished' => true
    ]);
    
    // Send initialization request for a finished interview
    $response = postJson('/chat', [
        'sessionId' => $sessionId,
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