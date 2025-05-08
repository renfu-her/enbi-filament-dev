<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use FilamentTiptapEditor\TiptapEditor;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = '商店管理';

    protected static ?string $modelLabel = '商品';

    protected static ?string $pluralModelLabel = '商品';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本資料')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('商品名稱'),
                        Forms\Components\RichEditor::make('description')
                            ->required()
                            ->label('商品描述')
                            ->columnSpanFull(),
                        FileUpload::make('image')
                            ->label('商品圖片')
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
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('商品圖片'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('商品名稱'),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('規格數量'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('啟用狀態'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('建立時間'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        '1' => '啟用',
                        '0' => '停用',
                    ])
                    ->label('啟用狀態'),
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function afterSave(Model $record, array $data): void
    {
        if (isset($data['image'])) {
            try {
                $relativePath = 'public/products/' . basename($data['image']);
                
                // 檢查檔案是否存在
                if (!Storage::exists($relativePath)) {
                    throw new \Exception('找不到上傳的圖片檔案：' . $relativePath);
                }
                
                // 讀取原始檔案
                $originalContent = Storage::get($relativePath);
                if (!$originalContent) {
                    throw new \Exception('無法讀取上傳的圖片檔案');
                }
                
                $manager = new ImageManager(new Driver());
                $image = $manager->read($originalContent);
                
                // 計算新的尺寸，保持比例
                $width = $image->width();
                $height = $image->height();
                $ratio = min(1920 / $width, 1920 / $height);
                $newWidth = round($width * $ratio);
                $newHeight = round($height * $ratio);
                
                // 調整圖片大小並轉換為 WebP
                $webpContent = $image->resize($newWidth, $newHeight)
                                   ->toWebp(90)
                                   ->toString();
                
                // 儲存 WebP 檔案
                $newPath = 'products/' . basename($data['image']) . '.webp';
                Storage::put('public/' . $newPath, $webpContent);
                
                // 更新記錄
                $record->image = $newPath;
                $record->save();
                
                // 刪除原始檔案
                Storage::delete($relativePath);
                
                Notification::make()
                    ->title('圖片處理成功')
                    ->success()
                    ->send();
            } catch (\Exception $e) {
                Notification::make()
                    ->title('圖片處理失敗')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        }
    }
} 