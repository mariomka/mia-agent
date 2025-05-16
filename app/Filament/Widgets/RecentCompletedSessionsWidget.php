<?php

namespace App\Filament\Widgets;

use App\Enums\InterviewSessionStatus;
use App\Filament\Resources\InterviewSessionResource;
use App\Models\InterviewSession;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class RecentCompletedSessionsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                InterviewSession::query()
                    ->with('interview')
                    ->whereIn('status', [
                        InterviewSessionStatus::completed,
                        InterviewSessionStatus::partiallyCompleted,
                    ])
                    ->latest()
                    ->limit(15)
            )
            ->recordUrl(
                fn (Model $record): string => InterviewSessionResource::getUrl('view', ['record' => $record])
            )
            ->columns([
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

                Tables\Columns\TextColumn::make('interview.name')
                    ->label('Interview')
                    ->sortable(),

                Tables\Columns\TextColumn::make('summary')
                    ->limit(50)
                    ->default('n/a'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->heading('Recent Completed Interview Sessions')
            ->paginated(false);
    }
}
