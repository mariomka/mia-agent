<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InterviewResource\Pages;
use App\Http\Controllers\InterviewController;
use App\Models\Interview;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class InterviewResource extends Resource
{
    protected static ?string $model = Interview::class;

    protected static ?string $navigationIcon = 'heroicon-o-microphone';

    protected static ?string $navigationLabel = 'Interviews';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make([
                    'default' => 1,
                    'md' => 3,
                ])
                    ->schema([
                        // Left Column (wider)
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make('Interview Details')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(122)
                                            ->autofocus()
                                            ->hintAction(
                                                Forms\Components\Actions\Action::make('name_info')
                                                    ->iconButton()
                                                    ->icon('heroicon-o-information-circle')
                                                    ->tooltip('A descriptive name for this interview template, it isn\'t public.')
                                                    ->color('gray')
                                            ),

                                        Forms\Components\Select::make('interview_type')
                                            ->required()
                                            ->options([
                                                'User Interview' => 'User Interview',
                                                'Screening Interview' => 'Screening Interview',
                                                'Customer Feedback' => 'Customer Feedback',
                                            ])
                                            ->hintAction(
                                                Forms\Components\Actions\Action::make('interview_type_info')
                                                    ->iconButton()
                                                    ->icon('heroicon-o-information-circle')
                                                    ->tooltip('Determines the interview purpose and AI behavior.')
                                                    ->color('gray')
                                            ),

                                        Forms\Components\TextInput::make('target_name')
                                            ->required()
                                            ->maxLength(100)
                                            ->hintAction(
                                                Forms\Components\Actions\Action::make('target_name_info')
                                                    ->iconButton()
                                                    ->icon('heroicon-o-information-circle')
                                                    ->tooltip('The name of the product, company, or subject being discussed in the interview.')
                                                    ->color('gray')
                                            ),

                                        Forms\Components\Textarea::make('target_description')
                                            ->maxLength(300)
                                            ->rows(4)
                                            ->hintAction(
                                                Forms\Components\Actions\Action::make('target_description_info')
                                                    ->iconButton()
                                                    ->icon('heroicon-o-information-circle')
                                                    ->tooltip('Provide context about the target that helps the AI agent understand what is being discussed.')
                                                    ->color('gray')
                                            ),
                                    ]),

                                Forms\Components\Repeater::make('topics')
                                    ->label('Topics')
                                    ->hintAction(
                                        Forms\Components\Actions\Action::make('topics_info')
                                            ->iconButton()
                                            ->icon('heroicon-o-information-circle')
                                            ->tooltip('Topics are the main questions the AI will ask during the interview. Each topic can have multiple follow-up questions based on the interviewee\'s responses. Add up to 10 topics to thoroughly explore your subject.')
                                            ->color('gray')
                                    )
                                    ->schema([
                                        Forms\Components\Hidden::make('key')
                                            ->default(fn () => Str::random(10)),

                                        Forms\Components\TextInput::make('question')
                                            ->label('Question')
                                            ->required()
                                            ->maxLength(122)
                                            ->hintAction(
                                                Forms\Components\Actions\Action::make('question_info')
                                                    ->iconButton()
                                                    ->icon('heroicon-o-information-circle')
                                                    ->tooltip('The specific topic that the AI agent will ask about. Make it clear and focused.')
                                                    ->color('gray')
                                            ),

                                        Forms\Components\Textarea::make('description')
                                            ->maxLength(300)
                                            ->rows(3)
                                            ->hintAction(
                                                Forms\Components\Actions\Action::make('description_info')
                                                    ->iconButton()
                                                    ->icon('heroicon-o-information-circle')
                                                    ->tooltip('Additional context for the AI about this topic\'s purpose and what information to gather')
                                                    ->color('gray')
                                            ),

                                        Forms\Components\ToggleButtons::make('approach')
                                            ->inline()
                                            ->options([
                                                'direct' => 'Direct',
                                                'indirect' => 'Indirect',
                                            ])
                                            ->default('direct')
                                            ->hintAction(
                                                Forms\Components\Actions\Action::make('approach_info')
                                                    ->iconButton()
                                                    ->icon('heroicon-o-information-circle')
                                                    ->tooltip('Direct approach addresses topics straightforwardly. Indirect approach uses examples or hypothetical scenarios instead of direct questions.')
                                                    ->color('gray')
                                            ),
                                    ])
                                    ->defaultItems(1)
                                    ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                                    ->collapsible()
                                    ->cloneable()
                                    ->addActionLabel('Add a topic')
                                    ->addActionAlignment(Alignment::Start)
                                    ->minItems(1)
                                    ->maxItems(10),
                            ])
                            ->columnSpan([
                                'default' => 1,
                                'md' => 2,
                            ]),

                        // Right Column (narrower)
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make('Agent Information')
                                    ->schema([
                                        Forms\Components\TextInput::make('agent_name')
                                            ->required()
                                            ->maxLength(40)
                                            ->default('Mia')
                                            ->hintAction(
                                                Forms\Components\Actions\Action::make('agent_name_info')
                                                    ->iconButton()
                                                    ->icon('heroicon-o-information-circle')
                                                    ->tooltip('The name the AI agent.')
                                                    ->color('gray')
                                            ),

                                        Forms\Components\Select::make('language')
                                            ->required()
                                            ->default('english')
                                            ->options([
                                                'english' => 'English',
                                                'spanish' => 'Spanish',
                                                'french' => 'French',
                                                'german' => 'German',
                                                'italian' => 'Italian',
                                                'portuguese' => 'Portuguese',
                                            ])
                                            ->hintAction(
                                                Forms\Components\Actions\Action::make('language_info')
                                                    ->iconButton()
                                                    ->icon('heroicon-o-information-circle')
                                                    ->tooltip('The language the AI agent will use throughout the conversation.')
                                                    ->color('gray')
                                            ),
                                    ]),

                                Forms\Components\Section::make('Timestamps')
                                    ->schema([
                                        Forms\Components\Placeholder::make('created_at')
                                            ->label('Created At')
                                            ->content(fn (Interview $record): string => $record?->created_at?->diffForHumans() ?? 'N/A')
                                            ->visible(fn ($record) => $record !== null),

                                        Forms\Components\Placeholder::make('updated_at')
                                            ->label('Last Updated')
                                            ->content(fn (Interview $record): string => $record?->updated_at?->diffForHumans() ?? 'N/A')
                                            ->visible(fn ($record) => $record !== null),
                                    ])
                                    ->visible(fn ($record) => $record !== null),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('interview_type')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('language')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('target_name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('interview_type')
                    ->options([
                        'User Interview' => 'User Interview',
                        'Screening Interview' => 'Screening Interview',
                        'Customer Feedback' => 'Customer Feedback',
                    ]),

                Tables\Filters\SelectFilter::make('language')
                    ->options([
                        'english' => 'English',
                        'spanish' => 'Spanish',
                        'french' => 'French',
                        'german' => 'German',
                        'italian' => 'Italian',
                        'portuguese' => 'Portuguese',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('open_interview')
                    ->label('Open Interview')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('success')
                    ->url(fn (Interview $record) => InterviewController::generateSignedUrl($record))
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\InterviewResource\RelationManagers\SessionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInterviews::route('/'),
            'create' => Pages\CreateInterview::route('/create'),
            'view' => Pages\ViewInterview::route('/{record}'),
            'edit' => Pages\EditInterview::route('/{record}/edit'),
        ];
    }
}
