<?php

namespace App\Filament\Resources\InterviewResource\Pages;

use App\Filament\Resources\InterviewResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInterview extends CreateRecord
{
    protected static string $resource = InterviewResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 