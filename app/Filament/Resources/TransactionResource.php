<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use WithFormFields;
use WithTableFilters;
use WithTableActions;
use WithPositionCalculations;
use Filament\Resources\Resource;
use Filament\Infolists\Components\Infolist;
use Filament\Infolists\Components\InfolistItem;
use Filament\Tables;
use Carbon\Carbon;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Livewire\Notifications;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Closure;
use Filament\Forms\Get;
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
                                    $set('stock', $product->stock); // Set stock value dynamically
                                    self::updateTotal($get, $set);
                                }
                            })
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                        TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->minValue(1)
                            ->afterStateUpdated(function (callable $get, callable $set) {
                                $quantity = (int)($get('quantity') ?? 0);
                                $product = Product::find($get('product_id'));
                                if ($product) {
                                    $stock = $product->stock;
                                    if ($quantity > $stock) {
                                        $set('quantity', $stock); // Reset to stock value if exceeded
                                    }
                                    $subtotal = $product->price * $quantity;
                                    $set('subtotal', $subtotal);
                                    self::updateTotal($get, $set);
                                }
                            }),

                        TextInput::make('subtotal')
                            ->label('Sub Total')
                            ->numeric()
                            ->prefix('Rp. ')
                            ->readonly(),

                        TextInput::make('stock')
                            ->label('Stock')
                            ->numeric()
                            ->readonly()
                            ->reactive()
                            ->afterStateUpdated(function (callable $get, callable $set) {
                                $product = Product::find($get('product_id'));
                                if ($product) {
                                    $set('stock', $product->stock); // Set stock dynamically
                                }
                            }),
                    ])
                    ->columns(['sm' => 1, 'md' => 2])
                    ->reactive()
                    ->disableItemMovement(),
            ])
            ->columnspan('1'),

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
                    ->required()
                    ->prefix('Rp. ')
                    ->hidden(fn(callable $get) => $get('payment_method') !== 'Cash')
                    ->afterStateUpdated(function (callable $get, callable $set) {
                        $cashReceived = (int)($get('cash_received') ?? 0);
                        $total = (int)($get('total') ?? 0);

                        if ($cashReceived < $total) {
                            $set('cash_received_error', 'Cash received cannot be less than the total amount.');
                            $set('change', 0);
                        } else {
                            $set('cash_received_error', null); // Clear the error if valid
                            $change = $cashReceived - $total;
                            $set('change', $change);
                        }
                    })
                    ->rules([
                        fn(Get $get): Closure => function ($attribute, $value, Closure $fail) use ($get) {
                            if ($get('total') > 0 && $get('cash_received') < $get('total')) {
                                $fail('Cash received cannot be less than the total amount.');
                            }
                        }
                    ])
                    ->reactive()
                    ->helperText(fn(callable $get) => $get('cash_received_error')), // Display error below the field

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
                    ->disk('public') // Use the public disk
                    ->directory('payment-proofs') // Save files in the 'payment-proofs' directory
                    ->image() // Validate to accept only images
                    ->acceptedFileTypes(['image/jpeg', 'image/png']) // Restrict file types
                    ->maxSize(2048) // Max file size in KB
                    ->required(fn(callable $get) => $get('payment_method') !== 'Cash') // Make this field mandatory for non-cash payments
                    ->hidden(fn(callable $get) => $get('payment_method') === 'Cash') // Hide for cash payments
                    ->reactive()
                    ->afterStateUpdated(function (callable $get, callable $set) {
                        if (!$get('payment_proof') && $get('payment_method') !== 'Cash') {
                            Notification::make()
                                ->title('Upload Required')
                                ->body('Please upload a payment proof for Bank Transfer or QRIS.')
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->columnspan('1'),
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
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->placeholder(fn($state): string => 'Dec 18, ' . now()->subYear()->format('Y')),
                        Forms\Components\DatePicker::make('created_until')
                            ->placeholder(fn($state): string => now()->format('M d, Y')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'Transaction from ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'Transaction until ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
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
