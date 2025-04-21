<?php

namespace App\Filament\Resources\InterviewSessionResource\Pages;

use App\Filament\Resources\InterviewSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewInterviewSession extends ViewRecord
{
    protected static string $resource = InterviewSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Interview Session Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('interview.name')
                            ->label('Interview Name'),
                        Infolists\Components\TextEntry::make('session_id')
                            ->label('Session ID'),
                        Infolists\Components\IconEntry::make('finished')
                            ->boolean()
                            ->label('Completed'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->dateTime(),
                    ]),

                Infolists\Components\Section::make('Summary & Topics')
                    ->schema([
                        Infolists\Components\TextEntry::make('summary')
                            ->markdown()
                            ->columnSpanFull(),
                        Infolists\Components\KeyValueEntry::make('topics')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Conversation')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('messages')
                            ->schema([
                                Infolists\Components\TextEntry::make('role')
                                    ->color(fn (array $state): string => match ($state) {
                                        'user' => 'info',
                                        'system' => 'warning',
                                        'assistant' => 'success',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('content')
                                    ->markdown()
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
} 