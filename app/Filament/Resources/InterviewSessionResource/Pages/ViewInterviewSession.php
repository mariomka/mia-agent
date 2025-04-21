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
                        Infolists\Components\TextEntry::make('topics')
                            ->formatStateUsing(function ($state) {
                                if (!is_array($state)) {
                                    return 'No topics data';
                                }
                                
                                $formattedOutput = '';
                                
                                foreach ($state as $index => $topic) {
                                    $formattedOutput .= "**Topic " . ($index + 1) . "**\n\n";
                                    
                                    if (isset($topic['key'])) {
                                        $formattedOutput .= "Key: " . $topic['key'] . "\n\n";
                                    }
                                    
                                    if (isset($topic['messages']) && is_array($topic['messages'])) {
                                        $formattedOutput .= "Messages: " . implode(', ', $topic['messages']) . "\n\n";
                                    }
                                    
                                    // Add other properties if they exist
                                    foreach ($topic as $key => $value) {
                                        if ($key !== 'key' && $key !== 'messages') {
                                            if (is_array($value)) {
                                                $formattedOutput .= $key . ": " . json_encode($value) . "\n\n";
                                            } else {
                                                $formattedOutput .= $key . ": " . $value . "\n\n";
                                            }
                                        }
                                    }
                                    
                                    $formattedOutput .= "---\n\n";
                                }
                                
                                return $formattedOutput;
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Conversation')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('messages')
                            ->schema([
                                Infolists\Components\TextEntry::make('role')
                                    ->color(fn ($state): string => match (is_array($state) ? json_encode($state) : $state) {
                                        'user' => 'info',
                                        'system' => 'warning',
                                        'assistant' => 'success',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('content')
                                    ->formatStateUsing(function ($state) {
                                        if (is_array($state)) {
                                            return json_encode($state, JSON_PRETTY_PRINT);
                                        }
                                        return $state;
                                    })
                                    ->markdown()
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
