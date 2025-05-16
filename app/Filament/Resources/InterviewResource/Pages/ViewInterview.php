<?php

namespace App\Filament\Resources\InterviewResource\Pages;

use App\Enums\InterviewStatus;
use App\Filament\Resources\InterviewResource;
use App\Http\Controllers\InterviewController;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\HtmlString;

class ViewInterview extends ViewRecord
{
    protected static string $resource = InterviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_url')
                ->label('Generate URL')
                ->icon('heroicon-o-link')
                ->color('gray')
                ->slideOver()
                ->modalWidth(MaxWidth::Large)
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->form([
                    Forms\Components\Placeholder::make('generated_url')
                        ->label('Interview URL')
                        ->key('generated_url')
                        ->hintAction(
                            Forms\Components\Actions\Action::make('open_url')
                                ->iconButton()
                                ->icon('heroicon-o-arrow-top-right-on-square')
                                ->tooltip('Open')
                                ->color('gray')
                                ->url(fn(Forms\Get $get) => $this->generateUrl($get('query_params') ?? []))
                                ->openUrlInNewTab()
                        )
                        ->content(function (Forms\Get $get): HtmlString {
                            $url = $this->generateUrl($get('query_params') ?? []);

                            return new HtmlString(<<<HTML
                                <div class="p-2 bg-gray-100 dark:bg-gray-800 rounded overflow-x-auto cursor-pointer">
                                    <code class="text-xs break-all" x-on:click="navigator.clipboard.writeText('$url'); new FilamentNotification().title('URL copied to clipboard').duration(1500).send()">$url</code>
                                </div>
                            HTML
                            );
                        }),

                    Forms\Components\Repeater::make('query_params')
                        ->label('Query Parameters')
                        ->schema([
                            Forms\Components\TextInput::make('key')
                                ->label('Parameter Name')
                                ->required()
                                ->live(true),
                            Forms\Components\TextInput::make('value')
                                ->label('Parameter Value')
                                ->live(true),
                        ])
                        ->collapsible()
                        ->collapsed(false)
                        ->addActionLabel('Add Parameter')
                        ->defaultItems(0)
                        ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                            $set('generated_url', $this->generateUrl($get('query_params')));
                        })
                        ->hintAction(
                            Forms\Components\Actions\Action::make('target_name_info')
                                ->iconButton()
                                ->icon('heroicon-o-information-circle')
                                ->tooltip('Add custom query parameters to the interview URL.')
                                ->color('gray')
                        ),
                ]),
            Actions\Action::make('open_interview')
                ->label('Open Interview')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('success')
                ->url(fn() => InterviewController::generateUrl($this->record))
                ->openUrlInNewTab(),
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    private function generateUrl(?array $params): string
    {
        $queryParams = [];
        foreach ($params as $param) {
            if (!empty($param['key'])) {
                $queryParams[$param['key']] = $param['value'] ?? '';
            }
        }

        return InterviewController::generateUrl($this->record, $queryParams);
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
                                                    $status = isset($topic['enabled']) && $topic['enabled'] === true ? '' : ' (disabled)';
                                                    $markdown .= "### Topic " . ($index + 1) . $status . "\n\n";

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
                                            ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                                    ]),

                                Infolists\Components\Section::make('Access')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('status')
                                            ->label('Status')
                                            ->badge()
                                            ->color(function (InterviewStatus $state): string {
                                                return match ($state) {
                                                    InterviewStatus::Draft => 'gray',
                                                    InterviewStatus::Published => 'success',
                                                    InterviewStatus::Completed => 'info',
                                                };
                                            }),
                                    ]),

                                Infolists\Components\Section::make('Statistics')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('sessions_count')
                                            ->label('Total Sessions')
                                            ->state(fn($record) => $record->sessions->count()),

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
