<?php

namespace App\Filament\Widgets;

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
        $completedSessions = InterviewSession::where('finished', true)->count();
        
        $completionRate = $totalSessions > 0 
            ? round(($completedSessions / $totalSessions) * 100, 1) 
            : 0;
            
        return [
            Stat::make('Total Interviews', $totalInterviews)
                ->description('Configured interviews')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success'),
                
            Stat::make('Total Sessions', $totalSessions)
                ->description('Interview sessions')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('primary'),
                
            Stat::make('Completion Rate', $completionRate . '%')
                ->description($completedSessions . ' completed sessions')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),
        ];
    }
} 