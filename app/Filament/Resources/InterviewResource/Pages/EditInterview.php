<?php

namespace App\Filament\Resources\InterviewResource\Pages;

use App\Filament\Resources\InterviewResource;
use App\Http\Controllers\InterviewController;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInterview extends EditRecord
{
    protected static string $resource = InterviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('open_interview')
                ->label('Open Interview')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('success')
                ->url(fn () => InterviewController::generateUrl($this->record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
