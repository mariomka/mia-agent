<?php

use App\Models\Interview;
use App\Models\InterviewSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('interview has many sessions', function () {
    $interview = Interview::factory()->create();
    InterviewSession::factory()->count(3)->create(['interview_id' => $interview->id]);
    
    expect($interview->sessions)->toHaveCount(3);
    expect($interview->sessions->first())->toBeInstanceOf(InterviewSession::class);
}); 