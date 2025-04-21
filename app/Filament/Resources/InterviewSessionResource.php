<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InterviewSessionResource\Pages;
use App\Models\InterviewSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InterviewSessionResource extends Resource
{
    protected static ?string $model = InterviewSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Interview Sessions';

    protected static ?string $recordTitleAttribute = 'session_id';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('interview_id')
                    ->relationship('interview', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('session_id')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Toggle::make('finished')
                    ->required(),

                Forms\Components\Textarea::make('summary')
                    ->rows(4)
                    ->columnSpanFull(),

                Forms\Components\Placeholder::make('topics')
                    ->content('Topics are stored in a structured array format and cannot be directly edited here. View the session for details.')
                    ->columnSpanFull(),

                Forms\Components\Placeholder::make('messages')
                    ->content('Messages data is stored in JSON format and can be viewed in the details page.')
                    ->columnSpanFull(),

                Forms\Components\Placeholder::make('metadata')
                    ->content('Query parameters are stored in metadata and can be viewed in the details page.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('interview.name')
                    ->label('Interview')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('session_id')
                    ->searchable(),

                Tables\Columns\IconColumn::make('finished')
                    ->boolean()
                    ->label('Completed'),

                Tables\Columns\TextColumn::make('summary')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('interview')
                    ->relationship('interview', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('finished')
                    ->label('Completed'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInterviewSessions::route('/'),
            'create' => Pages\CreateInterviewSession::route('/create'),
            'view' => Pages\ViewInterviewSession::route('/{record}'),
        ];
    }
}
