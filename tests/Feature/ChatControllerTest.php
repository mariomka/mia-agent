<?php

namespace Tests\Feature;

use App\Agents\InterviewAgent;
use App\Enums\InterviewStatus;
use App\Models\Interview;
use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class, WithFaker::class);

it('sends messages with session id', function () {
    $sessionId = Str::uuid()->toString();
    $interview = Interview::factory()->create();

    $this->mock(InterviewAgent::class, function ($mock) {
        $mock->shouldReceive('chat')
            ->once()
            ->andReturn([
                'messages' => ['Test response'],
                'finished' => false
            ]);
    });

    $response = $this->postJson('/chat', [
        'sessionId' => $sessionId,
        'chatInput' => 'Test message',
        'interviewId' => $interview->id
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'output' => [
                'messages' => ['Test response'],
                'finished' => false
            ]
        ]);

    $responseData = $response->json();
    $this->assertArrayNotHasKey('result', $responseData['output']);
});

it('requires session id and interview id', function () {
    $interview = Interview::factory()->create();

    $this->mock(InterviewAgent::class, function ($mock) {
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
    $response1->assertStatus(422);

    // Missing chatInput is valid (initialization)
    $response2 = $this->postJson('/chat', [
        'sessionId' => Str::uuid()->toString(),
        'interviewId' => $interview->id
    ]);
    $response2->assertStatus(200);

    // Missing interviewId
    $response3 = $this->postJson('/chat', [
        'sessionId' => Str::uuid()->toString(),
        'chatInput' => 'Test message'
    ]);
    $response3->assertStatus(422);
});

it('can initialize chat with empty message', function () {
    $sessionId = Str::uuid()->toString();
    $interview = Interview::factory()->create();

    $this->mock(InterviewAgent::class, function ($mock) {
        $mock->shouldReceive('chat')
            ->once()
            ->andReturn([
                'messages' => ['Welcome message'],
                'finished' => false
            ]);
    });

    $response = $this->postJson('/chat', [
        'sessionId' => $sessionId,
        'interviewId' => $interview->id
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'output' => [
                'messages' => ['Welcome message'],
                'finished' => false
            ]
        ]);
});

it('rejects new messages for finished interviews', function () {
    $interview = Interview::factory()->create();

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

    $this->mock(InterviewAgent::class, function ($mock) {
        $mock->shouldNotReceive('chat');
    });

    $response = postJson('/chat', [
        'sessionId' => $session->id,
        'interviewId' => $interview->id,
        'chatInput' => 'One more question...'
    ]);

    $response->assertStatus(400);

    $response->assertJson([
        'error' => 'This interview is already completed and cannot accept new messages.',
        'finished' => true
    ]);
});

it('filters out result data from response', function () {
    $interview = Interview::factory()->create();
    $sessionId = Str::uuid()->toString();

    $summary = "This is the interview summary.";
    $topics = [
        ['key' => 'topic1', 'messages' => ['Info 1', 'Info 2']]
    ];

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

    $response = postJson('/chat', [
        'sessionId' => $sessionId,
        'interviewId' => $interview->id,
        'chatInput' => 'My final answer'
    ]);

    $response->assertStatus(200);

    $response->assertJson([
        'output' => [
            'messages' => ['Thank you for your answers.'],
            'finished' => true
        ]
    ]);

    $responseData = $response->json();
    expect($responseData['output'])->not->toHaveKey('result');
});

it('rejects initialization for finished interviews', function () {
    $interview = Interview::factory()->create();

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

    $this->mock(InterviewAgent::class, function ($mock) {
        $mock->shouldNotReceive('chat');
    });

    $response = postJson('/chat', [
        'sessionId' => $session->id,
        'interviewId' => $interview->id
    ]);

    $response->assertStatus(400);

    $response->assertJson([
        'error' => 'This interview is already completed and cannot accept new messages.',
        'finished' => true
    ]);
});

it('handles very long messages appropriately', function () {
    $sessionId = Str::uuid()->toString();
    $interview = Interview::factory()->create();

    // Message exactly 200 characters - should pass
    $validMessage = str_repeat('a', 200);

    // Message over 200 characters - should fail
    $longMessage = str_repeat('b', 201);

    $this->mock(InterviewAgent::class, function ($mock) {
        $mock->shouldReceive('chat')
            ->once()
            ->andReturn([
                'messages' => ['Response to valid message'],
                'finished' => false
            ]);
    });

    // Valid message (exactly 200 chars)
    $validResponse = $this->postJson('/chat', [
        'sessionId' => $sessionId,
        'chatInput' => $validMessage,
        'interviewId' => $interview->id
    ]);

    $validResponse->assertStatus(200)
        ->assertJson([
            'output' => [
                'messages' => ['Response to valid message'],
                'finished' => false
            ]
        ]);

    // Too long message (over 200 chars)
    $invalidResponse = $this->postJson('/chat', [
        'sessionId' => $sessionId,
        'chatInput' => $longMessage,
        'interviewId' => $interview->id
    ]);

    $invalidResponse->assertStatus(422);
    $invalidResponse->assertJsonValidationErrors(['chatInput']);
    $invalidResponse->assertJson([
        'errors' => [
            'chatInput' => [
                'The chat input field must not be greater than 200 characters.'
            ]
        ]
    ]);
});

it('gracefully handles agent exceptions', function () {
    $sessionId = Str::uuid()->toString();
    $interview = Interview::factory()->create();

    $this->mock(InterviewAgent::class, function ($mock) {
        $mock->shouldReceive('chat')
            ->once()
            ->andThrow(new \Exception('Agent processing error'));
    });

    $response = $this->postJson('/chat', [
        'sessionId' => $sessionId,
        'chatInput' => 'Test message',
        'interviewId' => $interview->id
    ]);

    $response->assertStatus(500);

    $responseData = $response->json();
    expect($responseData)->toHaveKey('message');
    expect($responseData['message'])->toContain('Agent processing error');
});

it('returns 404 for draft interviews when user is not authenticated', function () {
    $sessionId = Str::uuid()->toString();
    $interview = Interview::factory()->create([
        'status' => InterviewStatus::Draft->value
    ]);

    $response = $this->postJson('/chat', [
        'sessionId' => $sessionId,
        'chatInput' => 'Test message',
        'interviewId' => $interview->id
    ]);

    $response->assertStatus(404);
});

it('allows access to draft interviews for authenticated users', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $sessionId = Str::uuid()->toString();
    $interview = Interview::factory()->create([
        'status' => InterviewStatus::Draft->value
    ]);

    $this->mock(InterviewAgent::class, function ($mock) {
        $mock->shouldReceive('chat')
            ->once()
            ->andReturn([
                'messages' => ['Test response'],
                'finished' => false
            ]);
    });

    $response = $this->postJson('/chat', [
        'sessionId' => $sessionId,
        'chatInput' => 'Test message',
        'interviewId' => $interview->id
    ]);

    $response->assertStatus(200);
});

it('returns 404 for completed interviews', function () {
    $sessionId = Str::uuid()->toString();
    $interview = Interview::factory()->create([
        'status' => InterviewStatus::Completed->value
    ]);

    $response = $this->postJson('/chat', [
        'sessionId' => $sessionId,
        'chatInput' => 'Test message',
        'interviewId' => $interview->id
    ]);

    $response->assertStatus(404);
});
