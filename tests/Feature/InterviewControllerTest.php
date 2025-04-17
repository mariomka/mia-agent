<?php

namespace Tests\Feature;

use App\Models\Interview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('generates session id when none exists', function () {
    // Create a public interview
    $interview = Interview::factory()->create([
        'is_public' => true,
    ]);

    // Visit the interview page
    $response = $this->get(route('interview', $interview));

    // Assert response is successful and uses the Chat view
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Chat'));

    // Check that the session contains an interview_session_id
    expect(session('interview_session_id'))->not->toBeNull();
    
    // Check that the session ID is a valid UUID
    expect(Str::isUuid(session('interview_session_id')))->toBeTrue();
    
    // Check that the session ID is passed to the frontend
    $response->assertInertia(fn ($page) => 
        $page->has('sessionId') && 
        $page->where('sessionId', session('interview_session_id'))
    );
});

it('reuses existing session id', function () {
    // Create a session ID
    $sessionId = Str::uuid()->toString();
    session(['interview_session_id' => $sessionId]);
    
    // Create a public interview
    $interview = Interview::factory()->create([
        'is_public' => true,
    ]);

    // Visit the interview page
    $response = $this->get(route('interview', $interview));

    // Assert response is successful
    $response->assertStatus(200);
    
    // Check that the existing session ID is reused
    expect(session('interview_session_id'))->toBe($sessionId);
    
    // Check that the session ID is passed to the frontend
    $response->assertInertia(fn ($page) => 
        $page->has('sessionId') && 
        $page->where('sessionId', $sessionId)
    );
});

it('requires valid signature for non public interviews', function () {
    // Create a private interview
    $interview = Interview::factory()->create([
        'is_public' => false,
    ]);

    // Visit the interview page without a signature
    $response = $this->get(route('interview', $interview));

    // Assert response is forbidden
    $response->assertStatus(403);
    
    // Visit with a signed URL
    $signedUrl = \App\Http\Controllers\InterviewController::generateSignedUrl($interview);
    $response = $this->get($signedUrl);
    
    // Assert response is successful
    $response->assertStatus(200);
});
