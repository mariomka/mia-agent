<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('interview:process-stale')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/interview-process-stale.log'));
