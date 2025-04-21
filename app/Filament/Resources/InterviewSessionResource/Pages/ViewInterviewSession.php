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
                        Infolists\Components\TextEntry::make('messages')
                            ->formatStateUsing(function ($state) {
                                if (!is_array($state)) {
                                    return 'No messages data';
                                }

                                $formattedOutput = '';
                                
                                foreach ($state as $index => $message) {
                                    if (is_array($message)) {
                                        $type = $message['type'] ?? 'unknown';
                                        $content = $message['content'] ?? 'No content';
                                        
                                        $typeLabel = match($type) {
                                            'user' => '👤 User',
                                            'assistant' => '🤖 Assistant',
                                            'system' => '⚙️ System',
                                            default => '❓ ' . $type,
                                        };
                                        
                                        $formattedOutput .= "### {$typeLabel}\n\n";
                                        $formattedOutput .= "{$content}\n\n";
                                        $formattedOutput .= "---\n\n";
                                    } else {
                                        $formattedOutput .= "### Message {$index}\n\n";
                                        $formattedOutput .= json_encode($message) . "\n\n";
                                        $formattedOutput .= "---\n\n";
                                    }
                                }
                                
                                return $formattedOutput;
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
