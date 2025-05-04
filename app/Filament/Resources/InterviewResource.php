<?php

namespace App\Filament\Resources;

use App\Enums\InterviewStatus;
use App\Filament\Resources\InterviewResource\Pages;
use App\Filament\Resources\InterviewResource\RelationManagers\SessionsRelationManager;
use App\Http\Controllers\InterviewController;
use App\Models\Interview;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class InterviewResource extends Resource
{
    protected static ?string $model = Interview::class;

    protected static ?string $navigationIcon = 'heroicon-o-microphone';

    protected static ?string $navigationLabel = 'Interviews';

    protected static ?string $recordTitleAttribute = 'name';

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

                                        Forms\Components\TextInput::make('interview_type')
                                            ->required()
                                            ->maxLength(100)
                                            ->datalist([
                                                'User Interview',
                                                'Screening Interview',
                                                'Customer Feedback',
                                                'Product Feedback',
                                                'Market Research',
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
                                            ->rows(3)
                                            ->hintAction(
                                                Forms\Components\Actions\Action::make('target_description_info')
                                                    ->iconButton()
                                                    ->icon('heroicon-o-information-circle')
                                                    ->tooltip('Provide context about the target that helps the AI agent understand what is being discussed.')
                                                    ->color('gray')
                                            ),

                                        Forms\Components\Textarea::make('target_additional_context')
                                            ->label('Additional Context')
                                            ->maxLength(1000)
                                            ->rows(6)
                                            ->hintAction(
                                                Forms\Components\Actions\Action::make('target_additional_context_info')
                                                    ->iconButton()
                                                    ->icon('heroicon-o-information-circle')
                                                    ->tooltip('Provide more detailed information about the target that will help the AI agent with deeper understanding during the interview.')
                                                    ->color('gray')
                                            ),
                                    ]),

                                Forms\Components\Section::make('Custom Messages')
                                    ->schema([
                                        Forms\Components\Textarea::make('welcome_message')
                                            ->label('Welcome Message')
                                            ->placeholder('Optional custom message to start the interview')
                                            ->maxLength(300)
                                            ->rows(3)
                                            ->hintAction(
                                                Forms\Components\Actions\Action::make('welcome_message_info')
                                                    ->iconButton()
                                                    ->icon('heroicon-o-information-circle')
                                                    ->tooltip('Custom message sent at the beginning of each interview. If empty, the AI will generate its own introduction.')
                                                    ->color('gray')
                                            ),

                                        Forms\Components\Textarea::make('goodbye_message')
                                            ->label('Goodbye Message')
                                            ->placeholder('Optional custom message to end the interview')
                                            ->maxLength(300)
                                            ->rows(3)
                                            ->hintAction(
                                                Forms\Components\Actions\Action::make('goodbye_message_info')
                                                    ->iconButton()
                                                    ->icon('heroicon-o-information-circle')
                                                    ->tooltip('Custom message sent at the end of each interview. If empty, the AI will generate its own conclusion.')
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
                                            ->maxLength(180)
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

                                        Forms\Components\TextInput::make('language')
                                            ->required()
                                            ->default('english')
                                            ->maxLength(40)
                                            ->datalist([
                                                'English',
                                                'Spanish',
                                                'French',
                                                'German',
                                                'Italian',
                                                'Portuguese',
                                            ])
                                            ->hintAction(
                                                Forms\Components\Actions\Action::make('language_info')
                                                    ->iconButton()
                                                    ->icon('heroicon-o-information-circle')
                                                    ->tooltip('The language the AI agent will use throughout the conversation.')
                                                    ->color('gray')
                                            ),
                                    ]),

                                Forms\Components\Section::make('Access')
                                    ->schema([
                                        Forms\Components\ToggleButtons::make('status')
                                            ->inline()
                                            ->options([
                                                InterviewStatus::Draft->value => 'Draft',
                                                InterviewStatus::Published->value => 'Published',
                                                InterviewStatus::Completed->value => 'Completed',
                                            ])
                                            ->default(InterviewStatus::Draft->value)
                                            ->required()
                                            ->hintAction(
                                                Forms\Components\Actions\Action::make('status_info')
                                                    ->iconButton()
                                                    ->icon('heroicon-o-information-circle')
                                                    ->tooltip('Draft: not publicly accessible. Published: available for interviews. Completed: no longer available for new interviews.')
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
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => fn (InterviewStatus $state): bool => $state === InterviewStatus::Draft,
                        'success' => fn (InterviewStatus $state): bool => $state === InterviewStatus::Published,
                        'info' => fn (InterviewStatus $state): bool => $state === InterviewStatus::Completed,
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('interview_type')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sessions_count')
                    ->label('Sessions')
                    ->state(function (Interview $record): string {
                        $totalSessions = $record->sessions()->count();
                        $completedSessions = $record->sessions()->where('finished', true)->count();
                        return "{$completedSessions} / {$totalSessions}";
                    })
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
                Tables\Filters\Filter::make('interview_type')
                    ->form([
                        Forms\Components\TextInput::make('interview_type')
                            ->label('Interview Type')
                            ->placeholder('Search by type...'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['interview_type'],
                            fn ($query) => $query->where('interview_type', 'like', "%{$data['interview_type']}%")
                        );
                    }),

                Tables\Filters\Filter::make('language')
                    ->form([
                        Forms\Components\TextInput::make('language')
                            ->label('Language')
                            ->placeholder('Search by language...'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['language'],
                            fn ($query) => $query->where('language', 'like', "%{$data['language']}%")
                        );
                    }),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        InterviewStatus::Draft->value => 'Draft',
                        InterviewStatus::Published->value => 'Published',
                        InterviewStatus::Completed->value => 'Completed',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('open_interview')
                    ->label('')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('success')
                    ->url(fn (Interview $record) => InterviewController::generateUrl($record))
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make()
                    ->label(''),
                Tables\Actions\EditAction::make()
                    ->label(''),
                Tables\Actions\DeleteAction::make()
                    ->label(''),
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
            SessionsRelationManager::class,
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
