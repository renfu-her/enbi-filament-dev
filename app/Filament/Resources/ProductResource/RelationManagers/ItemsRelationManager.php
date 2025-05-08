<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $recordTitleAttribute = 'name';
  
    protected static ?string $title = '商品規格';

    protected static ?string $navigationGroup = '商品管理';

    protected static ?string $modelLabel = '商品規格';

    protected static ?string $pluralModelLabel = '商品規格';

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('規格名稱'),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->label('價格'),
                Forms\Components\TextInput::make('sale_price')
                    ->numeric()
                    ->prefix('$')
                    ->label('優惠價'),
                Forms\Components\TextInput::make('stock')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->label('庫存'),
                Forms\Components\FileUpload::make('image')
                    ->label('規格圖片')
                    ->image()
                    ->imageEditor()
                    ->directory('products')
                    ->columnSpanFull()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->downloadable()
                    ->openable()
                    ->getUploadedFileNameForStorageUsing(
                        fn($file): string => (string) str(Str::uuid7() . '.webp')
                    )
                    ->saveUploadedFileUsing(function ($file) {
                        $manager = new ImageManager(new Driver());
                        $image = $manager->read($file);
                        
                        // 計算新的尺寸，保持比例
                        $width = $image->width();
                        $height = $image->height();
                        $ratio = min(1920 / $width, 1920 / $height);
                        $newWidth = round($width * $ratio);
                        $newHeight = round($height * $ratio);
                        
                        $image->resize($newWidth, $newHeight);
                        $filename = Str::uuid7()->toString() . '.webp';

                        if (!file_exists(storage_path('app/public/products'))) {
                            mkdir(storage_path('app/public/products'), 0755, true);
                        }

                        $image->toWebp(90)->save(storage_path('app/public/products/' . $filename));
                        return 'products/' . $filename;
                    })
                    ->deleteUploadedFileUsing(function ($file) {
                        if ($file) {
                            Storage::disk('public')->delete($file);
                        }
                    }),
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->inline(false)
                    ->default(true)
                    ->label('啟用狀態'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('規格圖片'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('規格名稱'),
                Tables\Columns\TextColumn::make('price')
                    ->money('TWD')
                    ->sortable()
                    ->label('價格'),
                Tables\Columns\TextColumn::make('sale_price')
                    ->money('TWD')
                    ->sortable()
                    ->label('優惠價'),
                Tables\Columns\TextColumn::make('stock')
                    ->numeric()
                    ->sortable()
                    ->label('庫存'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('啟用狀態'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        '1' => '啟用',
                        '0' => '停用',
                    ])
                    ->label('啟用狀態'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
