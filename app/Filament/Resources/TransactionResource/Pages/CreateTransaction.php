<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getCreatedNotification(): Notification|null
    {
        return Notification::make()
            ->success()
            ->title('Transaction created successfully!')
            ->icon('heroicon-s-check-circle')
            ->body('The transaction has been created.')
            ;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Transaction created successfully!';
    }

    
}
