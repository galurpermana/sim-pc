<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Infolists\Components\Infolist;
use Filament\Infolists\Components\InfolistItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use View;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Hidden::make('user_id')->default(auth()->id()),
            Section::make('Transaction Products')
                ->schema([
                    Repeater::make('transaction_products')
                        ->relationship('transactionProducts')
                        ->label('')
                        ->schema([
                            Select::make('product_id')
                                ->relationship('product', 'name')
                                ->label('Product')
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function (callable $get, callable $set) {
                                    $product = Product::find($get('product_id'));
                                    if ($product) {
                                        $quantity = (int)($get('quantity') ?? 0);
                                        $subtotal = $product->price * $quantity;
                                        $set('subtotal', $subtotal);
                                        self::updateTotal($get, $set);
                                    }
                                }),
                            TextInput::make('quantity')
                                ->numeric()
                                ->required()
                                ->reactive()
                                ->minValue(1)
                                ->afterStateUpdated(function (callable $get, callable $set) {
                                    $quantity = (int)($get('quantity') ?? 0);
                                    $product = Product::find($get('product_id'));
                                    if ($product) {
                                        $subtotal = $product->price * $quantity;
                                        $set('subtotal', $subtotal);
                                        self::updateTotal($get, $set);
                                    }
                                }),
                            TextInput::make('subtotal')
                                ->label('Sub Total')
                                ->numeric()
                                ->prefix('Rp. ')
                                ->readonly()
                                ->columnSpan('full'),
                        ])
                        ->columns(['sm' => 1, 
                        'md' => 2]) 
                        ->reactive()
                        ->disableItemMovement(),

                ])->columnspan('1'),

            Section::make('Transaction Details')
                ->schema([

                    TextInput::make('total')
                        ->numeric()
                        ->label('Total Amount')
                        ->required()
                        ->readonly()
                        ->prefix('Rp. ')
                        ->reactive()

                        ->default(0),

                    Select::make('payment_method')
                        ->label('Payment Method')
                        ->required()
                        ->default('Cash')
                        ->options([
                            'Cash' => 'Cash',
                            'Bank Transfer' => 'Bank Transfer',
                            'QRIS' => 'QRIS',
                        ])
                        ->reactive(),

                    TextInput::make('cash_received')
                        ->label('Cash Received')
                        ->numeric()
                        ->prefix('Rp. ')
                        
                        ->hidden(fn(callable $get) => $get('payment_method') !== 'Cash')
                        ->afterStateUpdated(function (callable $get, callable $set) {
                            $cashReceived = (int)($get('cash_received') ?? 0);
                            $total = (int)($get('total') ?? 0);
                            if ($cashReceived >= $total) {
                                $change = $cashReceived - $total;
                                $set('change', $change);
                            } else {
                                $set('change', 0);
                            }
                        })
                        ->reactive(),

                    TextInput::make('change')
                        ->label('Change')
                        ->numeric()
                        ->reactive()
                        ->prefix('Rp. ')
                        ->readonly()
                        ->default(0)
                        ->hidden(fn(callable $get) => $get('payment_method') !== 'Cash'),

                    
                ])->columnspan('1'),

        ]);
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set the authenticated user's ID
        $data['user_id'] = auth()->id();
    
        // Reduce stock for each product in transaction_products
        foreach ($data['transaction_products'] as &$transactionProduct) {
            $product = Product::find($transactionProduct['product_id']);
            if ($product) {
                // Check if there is enough stock
                if ($product->stock >= $transactionProduct['quantity']) {
                    // Reduce stock
                    $product->stock -= $transactionProduct['quantity'];
                    $product->save();
                } else {
                    // Handle insufficient stock (optional: throw an exception or set an error message)
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }
            }
        }
    
        return $data;
    }
    


    protected static function updateTotal(callable $get, callable $set)
    {
        // Calculate total from transaction products
        $transactionProducts = $get('../../transaction_products') ?? [];
        $total = array_reduce($transactionProducts, function ($carry, $item) {
            return $carry + (float)($item['subtotal'] ?? 0);
        }, 0);
        $set('../../total', $total);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->query(Transaction::with(['transactionProducts.product', 'user'])) // Eager load relationships
            ->columns([
                TextColumn::make('user.name')->label('Employee'),
                TextColumn::make('total')->label('Total')->currency('IDR'),
                TextColumn::make('created_at')->label('Date')->dateTime(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalWidth('7xl'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }





    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
