<?php

namespace App\Filament\Widgets;

use App\Enums\InterviewSessionStatus;
use App\Models\Interview;
use App\Models\InterviewSession;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalInterviews = Interview::count();
        $totalSessions = InterviewSession::count();
        $completedSessions = InterviewSession::where('status', [
            InterviewSessionStatus::completed,
        ])->count();

        $completionRate = $totalSessions > 0
            ? round(($completedSessions / $totalSessions) * 100, 1)
            : 0;

        return [
            Stat::make('Total Interviews', $totalInterviews),

            Stat::make('Total Sessions', $totalSessions),

            Stat::make('Completion Rate', $completionRate . '%'),
        ];
    }
}
