<?php

namespace App\Filament\Widgets;

use App\Models\InterviewSession;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentSessionsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                InterviewSession::query()
                    ->with('interview')
                    ->latest()
                    ->limit(15)
            )
            ->columns([
                TextColumn::make('interview.name')
                    ->label('Interview')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('finished')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),
                TextColumn::make('summary')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (InterviewSession $record): string => route('filament.admin.resources.interview-sessions.view', $record))
                    ->icon('heroicon-m-eye'),
            ])
            ->heading('Recent Interview Sessions')
            ->paginated(false);
    }
}
