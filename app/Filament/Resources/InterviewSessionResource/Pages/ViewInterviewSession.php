<?php

namespace App\Filament\Resources\InterviewSessionResource\Pages;

use App\Filament\Resources\InterviewSessionResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewInterviewSession extends ViewRecord
{
    protected static string $resource = InterviewSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Grid::make(3)
                    ->schema([
                        // Left column (wider - 75%)
                        Infolists\Components\Group::make()
                            ->schema([
                                Infolists\Components\Section::make('Summary')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('summary')
                                            ->label('')
                                            ->default('*No summary available*')
                                            ->markdown()
                                            ->columnSpanFull(),
                                    ]),

                                Infolists\Components\Section::make('Topics')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('topics_markdown')
                                            ->label('')
                                            ->state(function ($record) {
                                                if (!$record->topics || !is_array($record->topics)) {
                                                    return '*No topics data available*';
                                                }
                                                
                                                $markdown = '';
                                                
                                                foreach ($record->topics as $index => $topic) {
                                                    $markdown .= "### Topic " . ($index + 1) . "\n\n";
                                                
                                                    if (isset($topic['key'])) {
                                                        $markdown .= "**Key**: " . $topic['key'] . "\n\n";
                                                    }
                                                
                                                    if (isset($topic['messages']) && is_array($topic['messages'])) {
                                                        $markdown .= "**Messages**: \n\n";
                                                        $markdown .= implode("\n\n", array_map(fn($item) => (string) $item, $topic['messages']));
                                                        $markdown .= "\n\n";
                                                    }
                                                }
                                                
                                                return $markdown;
                                            })
                                            ->markdown()
                                            ->columnSpanFull(),
                                    ]),

                                Infolists\Components\Section::make('Conversation')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('messages_markdown')
                                            ->label('')
                                            ->state(function ($record) {
                                                if (!$record->messages || !is_array($record->messages)) {
                                                    return '*No messages available*';
                                                }
                                                
                                                $markdown = '';
                                                
                                                foreach ($record->messages as $index => $message) {
                                                    $type = is_array($message) && isset($message['type']) ? $message['type'] : 'unknown';
                                                    $content = is_array($message) && isset($message['content']) ? $message['content'] : json_encode($message);
                                                
                                                    $icon = match ($type) {
                                                        'user' => '👤',
                                                        'assistant' => '🤖',
                                                        default => '❓',
                                                    };
                                                
                                                    $markdown .= "**" . $icon . " " . ucfirst($type) . "**\n\n";
                                                    $markdown .= (string) $content . "\n\n";
                                                }
                                                
                                                return $markdown;
                                            })
                                            ->markdown()
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->columnSpan(2),

                        // Right column (narrower - 25%)
                        Infolists\Components\Group::make()
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
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }
}
