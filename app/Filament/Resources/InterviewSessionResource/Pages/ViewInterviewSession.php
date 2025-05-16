<?php

namespace App\Filament\Resources\InterviewSessionResource\Pages;

use App\Enums\InterviewSessionStatus;
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
                                                $interviewTopics = $record->interview->topics ?? [];

                                                // Create a lookup map to quickly find interview topics by key
                                                $topicMap = [];
                                                foreach ($interviewTopics as $topic) {
                                                    if (isset($topic['key'])) {
                                                        $topicMap[$topic['key']] = $topic;
                                                    }
                                                }

                                                foreach ($record->topics as $index => $topic) {
                                                    $markdown .= "### Topic " . ($index + 1) . "\n\n";

                                                    $key = $topic['key'] ?? null;

                                                    if ($key && isset($topicMap[$key]) && isset($topicMap[$key]['question'])) {
                                                        // Display the question from the interview's topic
                                                        $markdown .= "**Question**: " . $topicMap[$key]['question'] . "\n\n";
                                                    } else {
                                                        // Fallback to key if question isn't available
                                                        $markdown .= "**Key**: " . ($key ?? 'Unknown') . "\n\n";
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
                                            ->label('Interview')
                                            ->url(fn ($record) => route('filament.admin.resources.interviews.view', $record->interview->id)),
                                        Infolists\Components\TextEntry::make('session_id')
                                            ->label('Session ID'),

                                        Infolists\Components\TextEntry::make('status')
                                            ->label('Status')
                                            ->badge()
                                            ->formatStateUsing(fn (InterviewSessionStatus $state): string => $state->label())
                                            ->colors([
                                                'warning' => fn (InterviewSessionStatus $state): bool => $state === InterviewSessionStatus::inProgress,
                                                'success' => fn (InterviewSessionStatus $state): bool => $state === InterviewSessionStatus::completed,
                                                'info' => fn (InterviewSessionStatus $state): bool => $state === InterviewSessionStatus::partiallyCompleted,
                                                'danger' => fn (InterviewSessionStatus $state): bool => $state === InterviewSessionStatus::canceled,
                                            ]),

                                        Infolists\Components\TextEntry::make('created_at')
                                            ->dateTime(),
                                        Infolists\Components\TextEntry::make('updated_at')
                                            ->dateTime(),
                                    ]),

                                Infolists\Components\Section::make('Usage Statistics')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('input_tokens')
                                            ->label('Input Tokens'),
                                        Infolists\Components\TextEntry::make('output_tokens')
                                            ->label('Output Tokens'),
                                        Infolists\Components\TextEntry::make('cost')
                                            ->label('Total Cost')
                                            ->money('USD'),
                                    ]),

                                Infolists\Components\Section::make('Query Parameters')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('metadata_formatted')
                                            ->label('')
                                            ->state(function ($record) {
                                                if (!$record->metadata || !is_array($record->metadata) ||
                                                    !isset($record->metadata['query_parameters']) ||
                                                    !is_array($record->metadata['query_parameters']) ||
                                                    count($record->metadata['query_parameters']) === 0) {
                                                    return '*No query parameters available*';
                                                }

                                                $markdown = '';

                                                // Display query parameters
                                                $markdown .= "**Query Parameters:**\n\n";
                                                foreach ($record->metadata['query_parameters'] as $key => $value) {
                                                    $markdown .= "- **{$key}**: " . (is_array($value) ? json_encode($value) : $value) . "\n";
                                                }

                                                return $markdown;
                                            })
                                            ->markdown()
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }
}
