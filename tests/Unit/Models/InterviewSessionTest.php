<?php

use App\Enums\InterviewSessionStatus;
use App\Models\Interview;
use App\Models\InterviewSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('it has the correct fillable attributes', function () {
    $fillable = [
        'interview_id',
        'messages',
        'summary',
        'topics',
        'status',
        'metadata',
        'input_tokens',
        'output_tokens',
        'cost',
    ];

    $interviewSession = new InterviewSession();
    expect($interviewSession->getFillable())->toBe($fillable);
});

test('it casts attributes correctly', function () {
    $interviewSession = new InterviewSession();
    $casts = $interviewSession->getCasts();

    expect($casts['messages'])->toBe('array');
    expect($casts['topics'])->toBe('array');
    expect($casts['status'])->toBe(InterviewSessionStatus::class);;
});

test('it belongs to an interview', function () {
    $interview = Interview::factory()->create();
    $sessionId = (string) Str::uuid7();
    $interviewSession = InterviewSession::create([
        'id' => $sessionId,
        'interview_id' => $interview->id,
        'messages' => [],
    ]);

    expect($interviewSession->interview)->toBeInstanceOf(Interview::class);
    expect($interviewSession->interview->id)->toBe($interview->id);
});

test('it stores and retrieves messages as array', function () {
    $messages = [
        ['type' => 'user', 'content' => 'Hello'],
        ['type' => 'assistant', 'content' => 'Hi there!'],
    ];

    $interview = Interview::factory()->create();
    $sessionId = (string) Str::uuid7();
    $interviewSession = InterviewSession::create([
        'id' => $sessionId,
        'interview_id' => $interview->id,
        'messages' => $messages,
    ]);

    expect($interviewSession->messages)->toBeArray();
    expect($interviewSession->messages)->toBe($messages);
});

test('it stores and retrieves topics as array', function () {
    $topics = [
        ['key' => 'topic1', 'messages' => ['Message 1', 'Message 2']],
        ['key' => 'topic2', 'messages' => ['Message 3']],
    ];

    $interview = Interview::factory()->create();
    $sessionId = (string) Str::uuid7();
    $interviewSession = InterviewSession::create([
        'id' => $sessionId,
        'interview_id' => $interview->id,
        'messages' => [],
        'topics' => $topics,
    ]);

    expect($interviewSession->topics)->toBeArray();
    expect($interviewSession->topics)->toBe($topics);
});

test('session belongs to interview', function () {
    $interview = Interview::factory()->create();
    $session = InterviewSession::factory()->create(['interview_id' => $interview->id]);

    expect($session->interview)->toBeInstanceOf(Interview::class);
    expect($session->interview->id)->toBe($interview->id);
});

test('session attributes are properly cast', function () {
    $session = InterviewSession::factory()->create([
        'messages' => [['role' => 'system', 'content' => 'test']]
    ]);

    expect($session->messages)->toBeArray();
    expect($session->messages[0]['role'])->toBe('system');
});
