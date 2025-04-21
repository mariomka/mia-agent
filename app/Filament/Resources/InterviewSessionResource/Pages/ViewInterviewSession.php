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

                Infolists\Components\Section::make('Topics Data')
                    ->schema([
                        Infolists\Components\TextEntry::make('topics_data')
                            ->state(function ($record) {
                                if (!$record->topics || !is_array($record->topics)) {
                                    return '<div class="text-gray-500">No topics data available</div>';
                                }
                                
                                $html = '<div class="space-y-4">';
                                
                                foreach ($record->topics as $index => $topic) {
                                    $html .= '<div class="p-4 bg-gray-100 rounded-lg">';
                                    $html .= '<h3 class="text-lg font-bold">Topic ' . ($index + 1) . '</h3>';
                                    
                                    $html .= '<dl class="grid grid-cols-2 gap-2 mt-2">';
                                    
                                    if (is_array($topic)) {
                                        foreach ($topic as $key => $value) {
                                            $html .= '<dt class="font-medium">' . htmlspecialchars($key) . ':</dt>';
                                            
                                            if (is_array($value)) {
                                                $html .= '<dd>' . htmlspecialchars(json_encode($value)) . '</dd>';
                                            } else {
                                                $html .= '<dd>' . htmlspecialchars((string)$value) . '</dd>';
                                            }
                                        }
                                    } else {
                                        $html .= '<dd>' . htmlspecialchars((string)$topic) . '</dd>';
                                    }
                                    
                                    $html .= '</dl>';
                                    $html .= '</div>';
                                }
                                
                                $html .= '</div>';
                                
                                return $html;
                            })
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Conversation')
                    ->schema([
                        Infolists\Components\TextEntry::make('messages_data')
                            ->state(function ($record) {
                                if (!$record->messages || !is_array($record->messages)) {
                                    return '<div class="text-gray-500">No messages available</div>';
                                }
                                
                                $html = '<div class="space-y-4">';
                                
                                foreach ($record->messages as $index => $message) {
                                    $type = is_array($message) && isset($message['type']) ? $message['type'] : 'unknown';
                                    $content = is_array($message) && isset($message['content']) ? $message['content'] : json_encode($message);
                                    
                                    $bgColor = match($type) {
                                        'user' => 'bg-blue-100',
                                        'assistant' => 'bg-green-100',
                                        'system' => 'bg-yellow-100',
                                        default => 'bg-gray-100',
                                    };
                                    
                                    $icon = match($type) {
                                        'user' => '👤',
                                        'assistant' => '🤖',
                                        'system' => '⚙️',
                                        default => '❓',
                                    };
                                    
                                    $html .= '<div class="p-4 rounded-lg ' . $bgColor . '">';
                                    $html .= '<div class="font-bold">' . $icon . ' ' . ucfirst($type) . '</div>';
                                    $html .= '<div class="mt-2 whitespace-pre-wrap">' . htmlspecialchars((string)$content) . '</div>';
                                    $html .= '</div>';
                                }
                                
                                $html .= '</div>';
                                
                                return $html;
                            })
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
