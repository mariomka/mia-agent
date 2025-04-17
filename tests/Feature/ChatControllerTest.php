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
            ->andReturn((object)[
                'structured' => [
                    'message' => 'Test response',
                    'final_output' => null
                ]
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
                'message' => 'Test response',
                'final_output' => null
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
                
                return (object)[
                    'structured' => [
                        'message' => 'Mock response',
                        'final_output' => null
                    ]
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

it('requires session id chat input and interview id', function () {
    // Create a fake interview
    $interview = Interview::factory()->create();
    
    // Mock the InterviewAgent to avoid calling the real instance
    $this->mock(InterviewAgent::class, function ($mock) {
        // We don't expect this to be called, but mocking it prevents errors
        $mock->shouldReceive('chat')->andReturn((object)[
            'structured' => [
                'message' => 'Test response',
                'final_output' => null
            ]
        ]);
    });

    // Missing sessionId
    $response1 = $this->postJson('/chat', [
        'chatInput' => 'Test message',
        'interviewId' => $interview->id
    ]);
    $response1->assertStatus(422); // Validation error status

    // Missing chatInput
    $response2 = $this->postJson('/chat', [
        'sessionId' => Str::uuid()->toString(),
        'interviewId' => $interview->id
    ]);
    $response2->assertStatus(422);

    // Missing interviewId
    $response3 = $this->postJson('/chat', [
        'sessionId' => Str::uuid()->toString(),
        'chatInput' => 'Test message'
    ]);
    $response3->assertStatus(422);
});
