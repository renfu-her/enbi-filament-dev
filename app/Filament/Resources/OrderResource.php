<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = '商店管理';

    protected static ?string $modelLabel = '訂單';

    protected static ?string $pluralModelLabel = '訂單';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本資料')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->label('用戶'),
                        Forms\Components\TextInput::make('order_number')
                            ->required()
                            ->maxLength(255)
                            ->label('訂單編號'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => '待處理',
                                'processing' => '處理中',
                                'shipped' => '已出貨',
                                'delivered' => '已送達',
                                'cancelled' => '已取消',
                            ])
                            ->required()
                            ->label('訂單狀態'),
                        Forms\Components\TextInput::make('total_amount')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->label('總金額'),
                    ])->columns(2),
                Forms\Components\Section::make('收件資訊')
                    ->schema([
                        Forms\Components\TextInput::make('shipping_name')
                            ->required()
                            ->maxLength(255)
                            ->label('收件人'),
                        Forms\Components\TextInput::make('shipping_phone')
                            ->required()
                            ->tel()
                            ->maxLength(20)
                            ->label('收件電話'),
                        Forms\Components\TextInput::make('shipping_address')
                            ->required()
                            ->maxLength(255)
                            ->label('收件地址'),
                    ])->columns(2),
                Forms\Components\Section::make('付款資訊')
                    ->schema([
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'credit_card' => '信用卡',
                                'bank_transfer' => '銀行轉帳',
                                'cash_on_delivery' => '貨到付款',
                            ])
                            ->required()
                            ->label('付款方式'),
                        Forms\Components\Select::make('payment_status')
                            ->options([
                                'pending' => '待付款',
                                'paid' => '已付款',
                                'failed' => '付款失敗',
                                'refunded' => '已退款',
                            ])
                            ->required()
                            ->label('付款狀態'),
                    ])->columns(2),
                Forms\Components\Section::make('其他資訊')
                    ->schema([
                        Forms\Components\Textarea::make('note')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->label('備註'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->searchable()
                    ->sortable()
                    ->label('訂單編號'),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('用戶'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('TWD')
                    ->sortable()
                    ->label('總金額'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'processing' => 'warning',
                        'shipped' => 'info',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => '待處理',
                        'processing' => '處理中',
                        'shipped' => '已出貨',
                        'delivered' => '已送達',
                        'cancelled' => '已取消',
                    })
                    ->label('訂單狀態'),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => '待付款',
                        'paid' => '已付款',
                        'failed' => '付款失敗',
                        'refunded' => '已退款',
                    })
                    ->label('付款狀態'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('建立時間'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => '待處理',
                        'processing' => '處理中',
                        'shipped' => '已出貨',
                        'delivered' => '已送達',
                        'cancelled' => '已取消',
                    ])
                    ->label('訂單狀態'),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'pending' => '待付款',
                        'paid' => '已付款',
                        'failed' => '付款失敗',
                        'refunded' => '已退款',
                    ])
                    ->label('付款狀態'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
} 