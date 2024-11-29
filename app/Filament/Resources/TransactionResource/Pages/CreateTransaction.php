<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\Rules\Can;

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

    // protected function beforeCreate(): void {
    //     $transaction = $this->data[];

        
    //     if ($transaction && $transaction->cash_received === !null) {
    //         if ($transaction->cash_received < $transaction->total) {
    //             Notification::make()
    //                 ->danger()
    //                 ->title('Error')
    //                 ->body('Uang yang diterima kurang dari total transaksi')
    //                 ->send();
    //             $this->halt();
    //         }
    //     }  
    //     if ($transaction && $transaction->payment_method === 'Cash') {
    //         Notification::make()
    //             ->danger()    
    //             ->title('Error')
    //             ->body('Tidak ada uang yang diterima')
    //             ->send();
    //         $this->halt();
            
    //     }
    // }

    protected function afterCreate(): void
    {
        // Access the created transaction using $this->record (provided by Filament)
        $transaction = $this->record;
    
        try {
            // Loop through the transaction's associated products
            foreach ($transaction->transactionProducts as $transactionProduct) {
                // Retrieve the associated product
                $product = $transactionProduct->product;
    
                if ($product) {
                    // Check if there is enough stock available
                    if ($transactionProduct->quantity > $product->stock) {
                        throw new \Exception("Insufficient stock for product: {$product->name}");
                    }
    
                    // Reduce the stock by the quantity in the transaction
                    $product->stock -= $transactionProduct->quantity;
    
                    // Save the updated product stock
                    $product->save();
                } else {
                    throw new \Exception("Product not found for transaction product ID: {$transactionProduct->id}");
                }
            }
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body($e->getMessage());
        }
    }
    

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Transaction created successfully!';
    }

    
}
