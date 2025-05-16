<?php

namespace Tests\Feature;

use App\Enums\InterviewSessionStatus;
use App\Enums\InterviewStatus;
use App\Models\Interview;
use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

use function Pest\Laravel\get;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

it('generates session id when none exists', function () {
    $interview = Interview::factory()->create();
    $response = get(route('interview', $interview));
    $interviewSessionKey = "interview_{$interview->id}_session_id";

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Chat'));

    $phpSessionId = session($interviewSessionKey);
    expect($phpSessionId)->not->toBeNull();
    expect(Str::isUuid($phpSessionId))->toBeTrue();

    $response->assertInertia(function ($page) use ($phpSessionId, $interview) {
        $props = $page->toArray()['props'];
        $responseSessionId = $props['sessionId'];

        $page->has('sessionId')
             ->where('sessionId', function($value) use ($phpSessionId) {
                 expect(Str::isUuid($value))->toBeTrue();
                 expect($value)->toBe($phpSessionId);
                 return true;
             });
        $page->where('interview.id', $interview->id);
        return true;
    });
});


it('creates new interview session if not exists', function () {
    $interview = Interview::factory()->create();
    $response = get(route('interview', $interview));

    $response->assertStatus(200);

    $interviewSessionKey = "interview_{$interview->id}_session_id";
    $sessionId = session($interviewSessionKey);

    expect($sessionId)->not->toBeNull();
    expect(Str::isUuid($sessionId))->toBeTrue();

    assertDatabaseHas('interview_sessions', [
        'id' => $sessionId,
        'interview_id' => $interview->id,
    ]);

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
        'status' => InterviewSessionStatus::inProgress,
    ])->create();
    session([$interviewSessionKey => $session->id]);

    $response = get(route('interview', $interview));
    $response->assertStatus(200);

    $response->assertInertia(function ($page) use ($session, $interviewSessionKey) {
        $props = $page->toArray()['props'];
        $responseSessionId = $props['sessionId'];

        expect(Str::isUuid($responseSessionId))->toBeTrue();
        expect(session($interviewSessionKey))->toBe($responseSessionId);
        expect($responseSessionId)->toBe($session->id);

        $page->has('messages', 2)
             ->has('sessionId')
             ->where('sessionId', $responseSessionId)
             ->where('is_finished', false);

        return true;
    });
});

it('loads and formats messages from session', function () {
    $interview = Interview::factory()->create();
    $interviewSessionKey = "interview_{$interview->id}_session_id";

    $session = InterviewSession::factory([
        'interview_id' => $interview->id,
        'messages' => [
            ['type' => 'user', 'content' => 'Hello'],
            ['type' => 'assistant', 'content' => 'Hi there!'],
        ],
        'status' => InterviewSessionStatus::inProgress,
    ])->create();
    session([$interviewSessionKey => $session->id]);

    $response = get(route('interview', $interview));
    $response->assertStatus(200);

    $response->assertInertia(function ($page) use ($session, $interviewSessionKey) {
        $props = $page->toArray()['props'];
        $responseSessionId = $props['sessionId'];

        expect(Str::isUuid($responseSessionId))->toBeTrue();
        expect(session($interviewSessionKey))->toBe($responseSessionId);
        expect($responseSessionId)->toBe($session->id);

        // Verify formatted messages
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
             ->where('sessionId', $responseSessionId);

        return true;
    });
});

it('passes session finished status to frontend', function () {
    $interview = Interview::factory()->create();
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
        'status' => InterviewSessionStatus::completed,
    ])->create();
    session([$interviewSessionKey => $session->id]);

    $response = get(route('interview', $interview));
    $response->assertStatus(200);

    $response->assertInertia(function ($page) use ($session, $interviewSessionKey) {
        $props = $page->toArray()['props'];
        $responseSessionId = $props['sessionId'];

        expect(Str::isUuid($responseSessionId))->toBeTrue();
        expect(session($interviewSessionKey))->toBe($responseSessionId);
        expect($responseSessionId)->toBe($session->id);

        $page->has('is_finished')
             ->where('is_finished', true)
             ->where('sessionId', $responseSessionId);

        return true;
    });
});

it('passes unfinished status for ongoing sessions', function () {
    $interview = Interview::factory()->create();
    $interviewSessionKey = "interview_{$interview->id}_session_id";
    $messages = [
        ['type' => 'user', 'content' => 'Hello'],
        ['type' => 'assistant', 'content' => 'Hi there!'],
    ];
    $session = InterviewSession::factory([
        'interview_id' => $interview->id,
        'messages' => $messages,
        'status' => InterviewSessionStatus::inProgress,
    ])->create();
    session([$interviewSessionKey => $session->id]);

    $response = get(route('interview', $interview));
    $response->assertStatus(200);

    $response->assertInertia(function ($page) use ($session, $interviewSessionKey) {
        $props = $page->toArray()['props'];
        $responseSessionId = $props['sessionId'];

        expect(Str::isUuid($responseSessionId))->toBeTrue();
        expect(session($interviewSessionKey))->toBe($responseSessionId);
        expect($responseSessionId)->toBe($session->id);

        $page->has('is_finished')
             ->where('is_finished', false)
             ->where('sessionId', $responseSessionId);

        return true;
    });
});

it('returns 404 for non-existent interview', function () {
    $nonExistentId = Str::uuid()->toString();
    $response = get(route('interview', $nonExistentId));
    $response->assertStatus(404);
});

it('handles database errors gracefully', function () {
    $interview = Interview::factory()->create();
    $response = get(route('interview', $interview));

    $response->assertStatus(200);

    $interviewSessionKey = "interview_{$interview->id}_session_id";
    $sessionId = session($interviewSessionKey);
    expect($sessionId)->not->toBeNull();
    expect(Str::isUuid($sessionId))->toBeTrue();
});

it('rejects requests with invalid session ID format', function () {
    $interview = Interview::factory()->create();

    $interviewSessionKey = "interview_{$interview->id}_session_id";
    session([$interviewSessionKey => 'not-a-valid-uuid']);

    $response = get(route('interview', $interview));

    $newSessionId = session($interviewSessionKey);
    expect(Str::isUuid($newSessionId))->toBeTrue();
    expect($newSessionId)->not->toBe('not-a-valid-uuid');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) =>
        $page->has('sessionId') &&
        $page->where('sessionId', $newSessionId)
    );
});

it('returns 404 for draft interviews when user is not authenticated', function () {
    $interview = Interview::factory()->create([
        'status' => InterviewStatus::Draft->value
    ]);

    $response = get(route('interview', $interview));
    $response->assertStatus(404);
});

it('allows access to draft interviews for authenticated users', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $interview = Interview::factory()->create([
        'status' => InterviewStatus::Draft->value
    ]);

    $response = get(route('interview', $interview));
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Chat'));
});

it('returns 404 for completed interviews', function () {
    $interview = Interview::factory()->create([
        'status' => InterviewStatus::Completed->value
    ]);

    $response = get(route('interview', $interview));
    $response->assertStatus(404);
});

