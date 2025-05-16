<?php

namespace App\Filament\Resources;

use App\Enums\InterviewSessionStatus;
use App\Filament\Resources\InterviewSessionResource\Pages;
use App\Models\InterviewSession;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InterviewSessionResource extends Resource
{
    protected static ?string $model = InterviewSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Interview Sessions';

    protected static ?string $recordTitleAttribute = 'id';

    public static function canCreate(): bool
    {
        return false; // Disable creation of interview sessions in the admin panel
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (InterviewSessionStatus $state): string => $state->label())
                    ->colors([
                        'warning' => fn (InterviewSessionStatus $state): bool => $state === InterviewSessionStatus::IN_PROGRESS,
                        'success' => fn (InterviewSessionStatus $state): bool => $state === InterviewSessionStatus::COMPLETED,
                        'info' => fn (InterviewSessionStatus $state): bool => $state === InterviewSessionStatus::PARTIALLY_COMPLETED,
                        'danger' => fn (InterviewSessionStatus $state): bool => $state === InterviewSessionStatus::CANCELED,
                    ]),

                Tables\Columns\TextColumn::make('interview.name')
                    ->label('Interview')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('summary')
                    ->limit(50)
                    ->searchable()
                    ->default('n/a'),

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

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(InterviewSessionStatus::toArray())
                    ->multiple(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInterviewSessions::route('/'),
            'view' => Pages\ViewInterviewSession::route('/{record}'),
        ];
    }
}
