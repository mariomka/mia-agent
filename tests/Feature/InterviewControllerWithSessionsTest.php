<?php

namespace Tests\Feature;

use App\Models\Interview;
use App\Models\InterviewSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InterviewControllerWithSessionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_new_interview_session_if_not_exists()
    {
        // Create a public interview
        $interview = Interview::factory()->create([
            'is_public' => true,
        ]);
        
        // Visit the interview page
        $response = $this->get(route('interview', $interview));
        
        // Assert response is successful
        $response->assertStatus(200);
        
        // Get the session ID from the session
        $interviewSessionKey = "interview_{$interview->id}_session_id";
        $sessionId = session($interviewSessionKey);
        
        // Assert session ID is created
        $this->assertNotNull($sessionId);
        
        // Assert a database record is created
        $this->assertDatabaseHas('interview_sessions', [
            'session_id' => $sessionId,
            'interview_id' => $interview->id,
        ]);
    }
    
    /** @test */
    public function it_reuses_existing_interview_session()
    {
        // Create a public interview
        $interview = Interview::factory()->create([
            'is_public' => true,
        ]);
        
        // Generate the expected session key
        $interviewSessionKey = "interview_{$interview->id}_session_id";
        
        // Create a session ID for this specific interview
        $sessionId = "interview_{$interview->id}_" . Str::uuid()->toString();
        session([$interviewSessionKey => $sessionId]);
        
        // Create a pre-existing session in the database
        InterviewSession::create([
            'interview_id' => $interview->id,
            'session_id' => $sessionId,
            'messages' => [
                ['type' => 'user', 'content' => 'Hello'],
                ['type' => 'assistant', 'content' => 'Hi there!'],
            ],
        ]);
        
        // Visit the interview page
        $response = $this->get(route('interview', $interview));
        
        // Assert response is successful
        $response->assertStatus(200);
        
        // Check that the existing session ID is reused
        $this->assertEquals($sessionId, session($interviewSessionKey));
        
        // Check that the session data is passed to the frontend
        $response->assertInertia(fn ($page) => 
            $page->has('messages') && 
            $page->has('sessionId') && 
            $page->where('sessionId', $sessionId)
        );
    }
    
    /** @test */
    public function it_loads_messages_from_session()
    {
        // Create a public interview
        $interview = Interview::factory()->create([
            'is_public' => true,
        ]);
        
        // Generate the expected session key
        $interviewSessionKey = "interview_{$interview->id}_session_id";
        
        // Create a session ID for this specific interview
        $sessionId = "interview_{$interview->id}_" . Str::uuid()->toString();
        session([$interviewSessionKey => $sessionId]);
        
        // Create messages for the session
        $messages = [
            ['type' => 'user', 'content' => 'Hello'],
            ['type' => 'assistant', 'content' => 'Hi there!'],
        ];
        
        // Create a pre-existing session in the database
        InterviewSession::create([
            'interview_id' => $interview->id,
            'session_id' => $sessionId,
            'messages' => $messages,
        ]);
        
        // Visit the interview page
        $response = $this->get(route('interview', $interview));
        
        // Assert response is successful
        $response->assertStatus(200);
        
        // Check that the messages are passed to the frontend correctly
        $response->assertInertia(fn ($page) => 
            $page->has('messages', 2) && 
            $page->where('messages.0.text', 'Hello') &&
            $page->where('messages.1.text', 'Hi there!')
        );
    }
    
    /** @test */
    public function it_passes_session_finished_status_to_frontend()
    {
        // Create a public interview
        $interview = Interview::factory()->create([
            'is_public' => true,
        ]);
        
        // Generate the expected session key
        $interviewSessionKey = "interview_{$interview->id}_session_id";
        
        // Create a session ID for this specific interview
        $sessionId = "interview_{$interview->id}_" . Str::uuid()->toString();
        session([$interviewSessionKey => $sessionId]);
        
        // Create a summary and topics for the completed session
        $summary = "This is a summary of the interview.";
        $topics = [
            ['key' => 'topic1', 'messages' => ['Info 1', 'Info 2']],
            ['key' => 'topic2', 'messages' => ['Info 3']]
        ];
        
        // Create a finished session in the database
        InterviewSession::create([
            'interview_id' => $interview->id,
            'session_id' => $sessionId,
            'messages' => [
                ['type' => 'user', 'content' => 'Hello'],
                ['type' => 'assistant', 'content' => 'Hi there!'],
                ['type' => 'user', 'content' => 'Goodbye'],
                ['type' => 'assistant', 'content' => 'Thank you for completing this interview.'],
            ],
            'summary' => $summary,
            'topics' => $topics,
            'finished' => true
        ]);
        
        // Visit the interview page
        $response = $this->get(route('interview', $interview));
        
        // Assert response is successful
        $response->assertStatus(200);
        
        // Check that the session finished status is passed to the frontend
        $response->assertInertia(fn ($page) => 
            $page->has('is_finished') && 
            $page->where('is_finished', true)
        );
    }
    
    /** @test */
    public function it_passes_unfinished_status_for_ongoing_sessions()
    {
        // Create a public interview
        $interview = Interview::factory()->create([
            'is_public' => true,
        ]);
        
        // Generate the expected session key
        $interviewSessionKey = "interview_{$interview->id}_session_id";
        
        // Create a session ID for this specific interview
        $sessionId = "interview_{$interview->id}_" . Str::uuid()->toString();
        session([$interviewSessionKey => $sessionId]);
        
        // Create an ongoing session in the database
        InterviewSession::create([
            'interview_id' => $interview->id,
            'session_id' => $sessionId,
            'messages' => [
                ['type' => 'user', 'content' => 'Hello'],
                ['type' => 'assistant', 'content' => 'Hi there!'],
            ],
            'finished' => false
        ]);
        
        // Visit the interview page
        $response = $this->get(route('interview', $interview));
        
        // Assert response is successful
        $response->assertStatus(200);
        
        // Check that the session finished status is false
        $response->assertInertia(fn ($page) => 
            $page->has('is_finished') && 
            $page->where('is_finished', false)
        );
    }
}
