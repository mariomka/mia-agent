<?php

namespace App\Filament\Widgets;

use App\Models\InterviewSession;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SpendingStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $totalSpend = InterviewSession::sum('cost');

        $last30Days = InterviewSession::where('created_at', '>=', Carbon::now()->subDays())
            ->sum('cost');

        return [
            Stat::make('Total Spend', '$' . number_format($totalSpend, 2)),

            Stat::make('Last 30 Days Spend', '$' . number_format($last30Days, 2)),
        ];
    }
}
