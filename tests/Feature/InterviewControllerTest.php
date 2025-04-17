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

    // Generate the expected session key
    $interviewSessionKey = "interview_{$interview->id}_session_id";
    
    // Assert response is successful and uses the Chat view
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Chat'));

    // Check that the session contains an interview session ID
    expect(session($interviewSessionKey))->not->toBeNull();
    
    // Get the session ID
    $sessionId = session($interviewSessionKey);
    
    // Check that the session ID contains the interview ID
    expect($sessionId)->toContain("interview_{$interview->id}_");
    
    // Check that the UUID part is valid
    $uuidPart = str_replace("interview_{$interview->id}_", '', $sessionId);
    expect(Str::isUuid($uuidPart))->toBeTrue();
    
    // Check that the session ID is passed to the frontend
    $response->assertInertia(fn ($page) => 
        $page->has('sessionId') && 
        $page->where('sessionId', $sessionId)
    );
});

it('reuses existing session id', function () {
    // Create a public interview
    $interview = Interview::factory()->create([
        'is_public' => true,
    ]);
    
    // Generate the expected session key
    $interviewSessionKey = "interview_{$interview->id}_session_id";
    
    // Create a session ID for this specific interview
    $sessionId = "interview_{$interview->id}_" . Str::uuid()->toString();
    session([$interviewSessionKey => $sessionId]);
    
    // Visit the interview page
    $response = $this->get(route('interview', $interview));

    // Assert response is successful
    $response->assertStatus(200);
    
    // Check that the existing session ID is reused
    expect(session($interviewSessionKey))->toBe($sessionId);
    
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
