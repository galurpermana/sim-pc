<?php

namespace App\Filament\Resources\TransactionResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Transaction;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use App\Filament\Resources\TransactionResource\Pages\ListTransactions;

class weekly_revenue extends BaseWidget
{
    use InteractsWithPageTable;
    protected function getColumns(): int
    {
        return  2;
    }
    
    protected function getTablePage(): string
    {
        return ListTransactions::class;
    }
    protected function getStats(): array
    {
        return [
            // Stat::make( 'Weekly Revenue',Transaction::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek(),])->sum('total')),
            Stat::make('Weekly Revenue', 'Rp ' . number_format(Transaction::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('total'), 0, ',', '.')),

            // Stat::make(now()->format('F') . ' Revenue', Transaction::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('total')),
            Stat::make(" Revenue", 'Rp '.number_format($this->getPageTableQuery()->sum('total'), 0, ',', '.'))
        ];
    }
    
}
