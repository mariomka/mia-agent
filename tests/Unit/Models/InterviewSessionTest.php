<?php

namespace Tests\Unit\Models;

use App\Models\Interview;
use App\Models\InterviewSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterviewSessionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_the_correct_fillable_attributes()
    {
        $fillable = [
            'interview_id',
            'session_id',
            'messages',
            'summary',
            'topics',
            'finished',
        ];

        $interviewSession = new InterviewSession();
        $this->assertEquals($fillable, $interviewSession->getFillable());
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $interviewSession = new InterviewSession();
        $casts = $interviewSession->getCasts();

        $this->assertEquals('array', $casts['messages']);
        $this->assertEquals('array', $casts['topics']);
        $this->assertEquals('boolean', $casts['finished']);
    }

    /** @test */
    public function it_belongs_to_an_interview()
    {
        $interview = Interview::factory()->create();
        $interviewSession = InterviewSession::create([
            'interview_id' => $interview->id,
            'session_id' => 'test_session_id',
            'messages' => [],
        ]);

        $this->assertInstanceOf(Interview::class, $interviewSession->interview);
        $this->assertEquals($interview->id, $interviewSession->interview->id);
    }

    /** @test */
    public function it_stores_and_retrieves_messages_as_array()
    {
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

        $this->assertIsArray($interviewSession->messages);
        $this->assertEquals($messages, $interviewSession->messages);
    }

    /** @test */
    public function it_stores_and_retrieves_topics_as_array()
    {
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

        $this->assertIsArray($interviewSession->topics);
        $this->assertEquals($topics, $interviewSession->topics);
    }

    /** @test */
    public function it_has_default_value_for_finished()
    {
        $interview = Interview::factory()->create();
        $interviewSession = InterviewSession::create([
            'interview_id' => $interview->id,
            'session_id' => 'test_session_id',
            'messages' => [],
            'finished' => false,
        ]);

        $this->assertFalse($interviewSession->finished);
    }
}
