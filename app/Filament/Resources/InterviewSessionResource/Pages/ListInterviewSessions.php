<?php

namespace App\Filament\Resources\InterviewSessionResource\Pages;

use App\Filament\Resources\InterviewSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInterviewSessions extends ListRecords
{
    protected static string $resource = InterviewSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Remove CreateAction as interview sessions are created automatically via the website
        ];
    }
} 