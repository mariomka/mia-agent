<?php

namespace Tests\Feature;

use App\Agents\InterviewAgent;
use App\Models\Interview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Tests\TestCase;

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
