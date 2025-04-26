<?php

namespace App\Filament\Resources\InterviewResource\Pages;

use App\Filament\Resources\InterviewResource;
use App\Http\Controllers\InterviewController;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewInterview extends ViewRecord
{
    protected static string $resource = InterviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('open_interview')
                ->label('Open Interview')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('success')
                ->url(fn () => InterviewController::generateSignedUrl($this->record))
                ->openUrlInNewTab(),
            Actions\EditAction::make(),
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
                                Infolists\Components\Section::make('Interview Details')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->label('Name'),
                                        Infolists\Components\TextEntry::make('interview_type')
                                            ->label('Interview Type'),
                                        Infolists\Components\TextEntry::make('target_name')
                                            ->label('Target Name'),
                                        Infolists\Components\TextEntry::make('target_description')
                                            ->label('Target Description')
                                            ->markdown(),
                                    ]),

                                Infolists\Components\Section::make('Custom Messages')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('welcome_message')
                                            ->label('Welcome Message')
                                            ->placeholder('No custom welcome message defined'),

                                        Infolists\Components\TextEntry::make('goodbye_message')
                                            ->label('Goodbye Message')
                                            ->placeholder('No custom goodbye message defined'),
                                    ]),

                                Infolists\Components\Section::make('Topics')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('topics_formatted')
                                            ->label('')
                                            ->state(function ($record) {
                                                if (!$record->topics || !is_array($record->topics)) {
                                                    return '*No topics available*';
                                                }

                                                $markdown = '';

                                                foreach ($record->topics as $index => $topic) {
                                                    $markdown .= "### Topic " . ($index + 1) . "\n\n";

                                                    if (isset($topic['question'])) {
                                                        $markdown .= "**Question**: " . $topic['question'] . "\n\n";
                                                    }

                                                    if (isset($topic['description'])) {
                                                        $markdown .= "**Description**: " . $topic['description'] . "\n\n";
                                                    }

                                                    if (isset($topic['approach'])) {
                                                        $markdown .= "**Approach**: " . ucfirst($topic['approach']) . "\n\n";
                                                    }
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
                                Infolists\Components\Section::make('Agent Information')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('agent_name')
                                            ->label('Agent Name'),
                                        Infolists\Components\TextEntry::make('language')
                                            ->label('Language')
                                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                                    ]),

                                Infolists\Components\Section::make('Statistics')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('sessions_count')
                                            ->label('Total Sessions')
                                            ->state(fn ($record) => $record->sessions->count()),
                                        
                                        Infolists\Components\TextEntry::make('total_cost')
                                            ->label('Total Cost')
                                            ->state(function ($record) {
                                                return $record->sessions->sum('cost');
                                            })
                                            ->money('USD'),
                                    ]),

                                Infolists\Components\Section::make('Timestamps')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('created_at')
                                            ->label('Created At')
                                            ->dateTime(),
                                        Infolists\Components\TextEntry::make('updated_at')
                                            ->label('Updated At')
                                            ->dateTime(),
                                    ]),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }
}
