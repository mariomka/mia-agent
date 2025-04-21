<?php

use App\Models\Interview;
use App\Models\InterviewSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it has the correct fillable attributes', function () {
    $fillable = [
        'interview_id',
        'session_id',
        'messages',
        'summary',
        'topics',
        'finished',
        'metadata',
    ];

    $interviewSession = new InterviewSession();
    expect($interviewSession->getFillable())->toBe($fillable);
});

test('it casts attributes correctly', function () {
    $interviewSession = new InterviewSession();
    $casts = $interviewSession->getCasts();

    expect($casts['messages'])->toBe('array');
    expect($casts['topics'])->toBe('array');
    expect($casts['finished'])->toBe('boolean');
});

test('it belongs to an interview', function () {
    $interview = Interview::factory()->create();
    $interviewSession = InterviewSession::create([
        'interview_id' => $interview->id,
        'session_id' => 'test_session_id',
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
    $interviewSession = InterviewSession::create([
        'interview_id' => $interview->id,
        'session_id' => 'test_session_id',
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
    $interviewSession = InterviewSession::create([
        'interview_id' => $interview->id,
        'session_id' => 'test_session_id',
        'messages' => [],
        'topics' => $topics,
    ]);

    expect($interviewSession->topics)->toBeArray();
    expect($interviewSession->topics)->toBe($topics);
});

test('it has default value for finished', function () {
    $interview = Interview::factory()->create();
    $interviewSession = InterviewSession::create([
        'interview_id' => $interview->id,
        'session_id' => 'test_session_id',
        'messages' => [],
        'finished' => false,
    ]);

    expect($interviewSession->finished)->toBeFalse();
}); 