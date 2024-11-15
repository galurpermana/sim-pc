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
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
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

                    FileUpload::make('payment_proof')
                        ->label('Payment Proof')
                        ->disk('public') // Gunakan disk public
                        ->directory('payment-proofs') // Menyimpan file di direktori 'payment-proofs'
                        ->image() // Validasi hanya menerima gambar
                ])->columnspan('1'),
        ]);
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
                TextColumn::make('No.')
                    ->rowIndex(),
                TextColumn::make('user.name')->label('Employee')
                    
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total')->label('Total')->currency('IDR')
                    ->searchable(),
                    // ->sortable(),
                TextColumn::make('created_at')->label('Date')->dateTime()
                    ->sortable()
                    ->searchable(),
                ImageColumn::make('payment_proof')
                    ->label('Payment Proof')
                    ->disk('public') // Pastikan sesuai dengan disk yang digunakan di konfigurasi FileUpload
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalWidth('7xl')
                    ->slideOver(),
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
