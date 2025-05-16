<?php

namespace App\Filament\Resources\InterviewResource\RelationManagers;

use App\Enums\InterviewSessionStatus;
use App\Filament\Resources\InterviewSessionResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    protected static ?string $recordTitleAttribute = 'id';

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(
                fn (Model $record): string => InterviewSessionResource::getUrl('view', ['record' => $record])
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (InterviewSessionStatus $state): string => $state->label())
                    ->colors([
                        'warning' => fn (InterviewSessionStatus $state): bool => $state === InterviewSessionStatus::inProgress,
                        'success' => fn (InterviewSessionStatus $state): bool => $state === InterviewSessionStatus::completed,
                        'info' => fn (InterviewSessionStatus $state): bool => $state === InterviewSessionStatus::partiallyCompleted,
                        'danger' => fn (InterviewSessionStatus $state): bool => $state === InterviewSessionStatus::canceled,
                    ]),


                Tables\Columns\TextColumn::make('summary')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
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
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
