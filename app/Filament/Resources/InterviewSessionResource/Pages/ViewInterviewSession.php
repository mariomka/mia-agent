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

                Infolists\Components\Section::make('Summary')
                    ->schema([
                        Infolists\Components\TextEntry::make('summary')
                            ->markdown()
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Topics')
                    ->schema([
                        Infolists\Components\TextEntry::make('topics_markdown')
                            ->state(function ($record) {
                                if (!$record->topics || !is_array($record->topics)) {
                                    return '*No topics data available*';
                                }
                                
                                $markdown = '';
                                
                                foreach ($record->topics as $index => $topic) {
                                    $markdown .= "## Topic " . ($index + 1) . "\n\n";
                                    
                                    if (is_array($topic)) {
                                        if (isset($topic['key'])) {
                                            $markdown .= "**Key**: " . $topic['key'] . "\n\n";
                                        }
                                        
                                        if (isset($topic['messages']) && is_array($topic['messages'])) {
                                            $markdown .= "**Messages**: " . implode(', ', array_map(function($item) {
                                                return (string)$item;
                                            }, $topic['messages'])) . "\n\n";
                                        }
                                        
                                        // Add other properties
                                        foreach ($topic as $key => $value) {
                                            if ($key !== 'key' && $key !== 'messages') {
                                                $markdown .= "**" . $key . "**: ";
                                                
                                                if (is_array($value)) {
                                                    $markdown .= json_encode($value);
                                                } else {
                                                    $markdown .= (string)$value;
                                                }
                                                
                                                $markdown .= "\n\n";
                                            }
                                        }
                                    } else {
                                        $markdown .= (string)$topic . "\n\n";
                                    }
                                    
                                    $markdown .= "---\n\n";
                                }
                                
                                return $markdown;
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Conversation')
                    ->schema([
                        Infolists\Components\TextEntry::make('messages_markdown')
                            ->state(function ($record) {
                                if (!$record->messages || !is_array($record->messages)) {
                                    return '*No messages available*';
                                }
                                
                                $markdown = '';
                                
                                foreach ($record->messages as $index => $message) {
                                    $type = is_array($message) && isset($message['type']) ? $message['type'] : 'unknown';
                                    $content = is_array($message) && isset($message['content']) ? $message['content'] : json_encode($message);
                                    
                                    $icon = match($type) {
                                        'user' => '👤',
                                        'assistant' => '🤖',
                                        'system' => '⚙️',
                                        default => '❓',
                                    };
                                    
                                    $markdown .= "### " . $icon . " " . ucfirst($type) . "\n\n";
                                    $markdown .= (string)$content . "\n\n";
                                    $markdown .= "---\n\n";
                                }
                                
                                return $markdown;
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
