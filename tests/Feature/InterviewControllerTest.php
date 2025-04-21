<?php

namespace Tests\Feature;

use App\Models\Interview;
use App\Models\InterviewSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

use function Pest\Laravel\get;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

it('generates session id when none exists', function () {
    // Create an interview
    $interview = Interview::factory()->create();

    // Visit the interview page
    $response = get(route('interview', $interview));

    // Generate the expected session key
    $interviewSessionKey = "interview_{$interview->id}_session_id";

    // Assert response is successful and uses the Chat view
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Chat'));

    // Check that the PHP session now contains a valid UUID
    $phpSessionId = session($interviewSessionKey);
    expect($phpSessionId)->not->toBeNull();
    expect(Str::isUuid($phpSessionId))->toBeTrue();

    // Check that the session ID passed to the frontend is a valid UUID
    // and matches the one now in the PHP session
    $response->assertInertia(function ($page) use ($phpSessionId, $interview) {
        $props = $page->toArray()['props'];
        $responseSessionId = $props['sessionId'];

        $page->has('sessionId')
             ->where('sessionId', function($value) use ($phpSessionId) {
                 expect(Str::isUuid($value))->toBeTrue();
                 // Check the ID in the props matches the ID now in the PHP session
                 expect($value)->toBe($phpSessionId);
                 return true;
             });
        // Check interview ID consistency
        $page->where('interview.id', $interview->id); // Use the passed $interview
        return true;
    });
});


it('creates new interview session if not exists', function () {
    // Create an interview
    $interview = Interview::factory()->create();

    // Visit the interview page (no prior session)
    $response = get(route('interview', $interview));

    // Assert response is successful
    $response->assertStatus(200);

    // Get the session ID from the session storage after the request
    $interviewSessionKey = "interview_{$interview->id}_session_id";
    $sessionId = session($interviewSessionKey);

    // Assert a valid session ID was created and stored
    expect($sessionId)->not->toBeNull();
    expect(Str::isUuid($sessionId))->toBeTrue();

    // Assert a database record was created with this ID and the correct interview_id
    assertDatabaseHas('interview_sessions', [
        'id' => $sessionId,
        'interview_id' => $interview->id,
    ]);

    // Assert the created sessionId is passed to frontend
    $response->assertInertia(fn ($page) =>
        $page->has('sessionId') &&
        $page->where('sessionId', $sessionId)
    );
});

it('reuses existing interview session with database', function () {
    $interview = Interview::factory()->create();

    $interviewSessionKey = "interview_{$interview->id}_session_id";

    $session = InterviewSession::factory([
        'interview_id' => $interview->id,
        'messages' => [
            ['type' => 'user', 'content' => 'Hello'],
            ['type' => 'assistant', 'content' => 'Hi there!'],
        ],
        'finished' => false // Ensure finished is false for this test
    ])->create();
    session([$interviewSessionKey => $session->id]);

    // Visit the interview page
    $response = get(route('interview', $interview));

    // Assert response is successful
    $response->assertStatus(200);

    // Check that the correct session ID, messages, and finished status are passed to the frontend
    $response->assertInertia(function ($page) use ($session, $interviewSessionKey) {
        $props = $page->toArray()['props'];
        $responseSessionId = $props['sessionId'];

        // Controller should return a valid UUID (might be new or reused)
        expect(Str::isUuid($responseSessionId))->toBeTrue();
        // PHP session should match the response ID
        expect(session($interviewSessionKey))->toBe($responseSessionId);
        // *** Ensure the controller reused the expected session ID ***
        expect($responseSessionId)->toBe($session->id);

        $page->has('messages', 2) // Check messages are loaded
             ->has('sessionId')
             ->where('sessionId', $responseSessionId) // Use the response ID for checks
             ->where('is_finished', false); // Check finished status

        return true;
    });
});

it('loads and formats messages from session', function () {
    // Create an interview
    $interview = Interview::factory()->create();

    $interviewSessionKey = "interview_{$interview->id}_session_id";

    $session = InterviewSession::factory([
        'interview_id' => $interview->id,
        'messages' => [
            ['type' => 'user', 'content' => 'Hello'],
            ['type' => 'assistant', 'content' => 'Hi there!'],
        ],
        'finished' => false // Ensure finished is false for this test
    ])->create();
    session([$interviewSessionKey => $session->id]);

    // Visit the interview page
    $response = get(route('interview', $interview));

    // Assert response is successful
    $response->assertStatus(200);

    // Check that the messages are passed to the frontend correctly
    $response->assertInertia(function ($page) use ($session, $interviewSessionKey) {
        $props = $page->toArray()['props']; // Get props array
        $responseSessionId = $props['sessionId'];

        // Controller should return a valid UUID (might be new or reused)
        expect(Str::isUuid($responseSessionId))->toBeTrue();
        // Verify the final PHP session ID matches the response ID
        expect(session($interviewSessionKey))->toBe($responseSessionId);
        // *** Ensure the controller reused the expected session ID ***
        expect($responseSessionId)->toBe($session->id);

        // Expected formatted messages using the response session ID
        $expectedMessages = [
            [
                'id' => "{$responseSessionId}_0",
                'sender' => 'user',
                'text' => 'Hello',
                'status' => 'sent'
            ],
            [
                'id' => "{$responseSessionId}_1",
                'sender' => 'ai',
                'text' => 'Hi there!',
                'status' => 'sent'
            ],
        ];

        $page->has('messages', 2)
             ->where('messages', $expectedMessages)
             ->where('sessionId', $responseSessionId); // Assert the ID in props

        return true; // Indicate successful assertion block
    });
});

it('passes session finished status to frontend', function () {
    // Create an interview
    $interview = Interview::factory()->create();

    // Generate a session ID and store it in the PHP session & DB
    $interviewSessionKey = "interview_{$interview->id}_session_id";

    $messages = [
        ['type' => 'user', 'content' => 'Hello'],
        ['type' => 'assistant', 'content' => 'Hi there!'],
        ['type' => 'user', 'content' => 'Goodbye'],
        ['type' => 'assistant', 'content' => 'Thank you for completing this interview.'],
    ];
    $summary = "This is a summary of the interview.";
    $topics = [
        ['key' => 'topic1', 'messages' => ['Info 1', 'Info 2']],
        ['key' => 'topic2', 'messages' => ['Info 3']]
    ];
    $session = InterviewSession::factory([
        'interview_id' => $interview->id,
        'messages' => $messages,
        'summary' => $summary,
        'topics' => $topics,
        'finished' => true
    ])->create();
    session([$interviewSessionKey => $session->id]);

    // Visit the interview page
    $response = get(route('interview', $interview));

    // Assert response is successful
    $response->assertStatus(200);

    // Check that the session finished status is passed to the frontend
    $response->assertInertia(function ($page) use ($session, $interviewSessionKey) {
        $props = $page->toArray()['props'];
        $responseSessionId = $props['sessionId'];

        // Controller should return a valid UUID (might be new or reused)
        expect(Str::isUuid($responseSessionId))->toBeTrue();
        // PHP session should match this response ID
        expect(session($interviewSessionKey))->toBe($responseSessionId);
        // *** Ensure the controller reused the expected session ID ***
        expect($responseSessionId)->toBe($session->id);

        $page->has('is_finished')
             ->where('is_finished', true)
             ->where('sessionId', $responseSessionId); // Use response ID

        return true;
    });
});

it('passes unfinished status for ongoing sessions', function () {
    // Create an interview
    $interview = Interview::factory()->create();

    $interviewSessionKey = "interview_{$interview->id}_session_id";
    $messages = [
        ['type' => 'user', 'content' => 'Hello'],
        ['type' => 'assistant', 'content' => 'Hi there!'],
    ];
    $session = InterviewSession::factory([
        'interview_id' => $interview->id,
        'messages' => $messages,
        'finished' => false
    ])->create();
    session([$interviewSessionKey => $session->id]);

    // Visit the interview page
    $response = get(route('interview', $interview));

    // Assert response is successful
    $response->assertStatus(200);

    // Check that the session finished status is false
    $response->assertInertia(function ($page) use ($session, $interviewSessionKey) {
        $props = $page->toArray()['props'];
        $responseSessionId = $props['sessionId'];

        // Controller should return a valid UUID (might be new or reused)
        expect(Str::isUuid($responseSessionId))->toBeTrue();
        // PHP session should match this response ID
        expect(session($interviewSessionKey))->toBe($responseSessionId);
        // *** Ensure the controller reused the expected session ID ***
        expect($responseSessionId)->toBe($session->id);

        $page->has('is_finished')
             ->where('is_finished', false)
             ->where('sessionId', $responseSessionId); // Use response ID

        return true;
    });
});
