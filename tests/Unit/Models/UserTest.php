<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user attributes are properly cast', function () {
    $user = User::factory()->create();
    
    expect($user->email_verified_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
}); 