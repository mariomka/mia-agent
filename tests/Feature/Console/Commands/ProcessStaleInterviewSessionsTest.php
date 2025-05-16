<?php

use App\Agents\InterviewAgent;
use App\Enums\InterviewSessionStatus;
use App\Models\Interview;
use App\Models\InterviewSession;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('processes stale sessions with user messages correctly', function () {
    // Mock the InterviewAgent class
    $this->mock(InterviewAgent::class)
        ->shouldReceive('chat')
        ->once()
        ->andReturn([
            'summary' => 'Test summary',
            'topics' => [
                [
                    'key' => 'test_topic',
                    'messages' => ['Test message']
                ]
            ]
        ]);

    // Create an interview
    $interview = Interview::factory()->create();

    // Create a stale session with user messages
    $staleSession = InterviewSession::create([
        'id' => '123e4567-e89b-12d3-a456-426614174000',
        'interview_id' => $interview->id,
        'messages' => [
            ['type' => 'assistant', 'content' => 'Hello, how can I help you?'],
            ['type' => 'user', 'content' => 'I need information about your services.']
        ],
        'status' => InterviewSessionStatus::inProgress,
        'metadata' => [],
        'input_tokens' => 0,
        'output_tokens' => 0,
        'cost' => 0,
    ]);

    // Set the updated_at timestamp to be more than 2 hours ago
    $staleSession->updated_at = Carbon::now()->subHours(2)->subMinute();
    $staleSession->save();

    // Run the command
    $this->artisan('interview:process-stale')
        ->assertSuccessful()
        ->expectsOutput('Found 1 stale interview sessions.');

    // Refresh the session from the database
    $staleSession->refresh();

    // Assert that the session status is now PARTIALLY_COMPLETED
    expect($staleSession->status->value)->toBe(InterviewSessionStatus::partiallyCompleted->value)
        ->and($staleSession->summary)->toBe('Test summary')
        ->and($staleSession->topics)->toBe([
            [
                'key' => 'test_topic',
                'messages' => ['Test message']
            ]
        ]);

    // Assert that the summary and topics were updated
});

it('processes stale sessions without user messages correctly', function () {
    // Create an interview
    $interview = Interview::factory()->create();

    // Create a stale session with user messages
    $staleSession = InterviewSession::create([
        'id' => '123e4567-e89b-12d3-a456-426614174000',
        'interview_id' => $interview->id,
        'messages' => [
            ['type' => 'assistant', 'content' => 'Hello, how can I help you?'],
        ],
        'status' => InterviewSessionStatus::inProgress,
        'metadata' => [],
        'input_tokens' => 0,
        'output_tokens' => 0,
        'cost' => 0,
    ]);

    // Set the updated_at timestamp to be more than 2 hours ago
    $staleSession->updated_at = Carbon::now()->subHours(2)->subMinute();
    $staleSession->save();

    // Run the command
    $this->artisan('interview:process-stale')
        ->assertSuccessful()
        ->expectsOutput('Found 1 stale interview sessions.');

    // Refresh the session from the database
    $staleSession->refresh();

    // Assert that the session status is now CANCELED
    expect($staleSession->status)->toBe(InterviewSessionStatus::canceled);
});

it('does not process recent sessions', function () {
    // Create an interview
    $interview = Interview::factory()->create();

    // Create a non-stale session
    $session = InterviewSession::create([
        'id' => '123e4567-e89b-12d3-a456-426614174002',
        'interview_id' => $interview->id,
        'messages' => [
            ['type' => 'assistant', 'content' => 'Hello, how can I help you?'],
            ['type' => 'user', 'content' => 'I need information about your services.']
        ],
        'status' => InterviewSessionStatus::inProgress,
        'metadata' => [],
        'input_tokens' => 0,
        'output_tokens' => 0,
        'cost' => 0,
    ]);

    // Set the updated_at timestamp to be less than 2 hours ago
    $session->updated_at = Carbon::now()->subHours(2)->addMinute();
    $session->save();

    // Run the command
    $this->artisan('interview:process-stale')
        ->assertSuccessful()
        ->expectsOutput('Found 0 stale interview sessions.');

    // Refresh the session from the database
    $session->refresh();

    // Assert that the session status is still IN_PROGRESS
    expect($session->status)->toBe(InterviewSessionStatus::inProgress);
});
