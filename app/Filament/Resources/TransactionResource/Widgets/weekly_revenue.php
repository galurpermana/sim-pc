<?php

namespace App\Filament\Resources\TransactionResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Transaction;

class weekly_revenue extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make( 'Weekly Revenue',Transaction::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek(),])->sum('total')),
            Stat::make( 'Monthly Revenue',Transaction::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth(),])->sum('total')),
        ];
    }
}
