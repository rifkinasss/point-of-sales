<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Marcelorodrigo\FilamentBarcodeScannerField\Forms\Components\BarcodeInput;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('unit_id')
                    ->label('Satuan')
                    ->relationship('unit', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->label('Nama produk')
                    ->required()
                    ->maxLength(180),
                TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->maxLength(80)
                    ->unique(ignoreRecord: true),
                BarcodeInput::make('barcode')
                    ->label('Barcode')
                    ->helperText('Gunakan ikon pemindai untuk membaca barcode dari kamera, atau ketikkan secara manual.')
                    ->maxLength(100)
                    ->unique(ignoreRecord: true),
                FileUpload::make('image_path')
                    ->label('Foto produk')
                    ->image()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('products')
                    ->maxSize(2048),
                Select::make('product_type')
                    ->label('Tipe produk')
                    ->options(ProductType::class)
                    ->default(ProductType::Stockable)
                    ->required(),
                TextInput::make('cost_price')
                    ->label('Harga beli referensi')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->prefix('Rp'),
                TextInput::make('selling_price')
                    ->label('Harga jual')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp'),
                TextInput::make('minimum_stock')
                    ->label('Stok minimum')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                Repeater::make('optionGroups')
                    ->label('Opsi & add-on')
                    ->relationship()
                    ->schema([
                        TextInput::make('name')->label('Nama pilihan')->placeholder('Contoh: Level gula')->required(),
                        Toggle::make('is_required')->label('Wajib dipilih'),
                        TextInput::make('minimum_selections')->label('Min. pilihan')->numeric()->default(0)->minValue(0),
                        TextInput::make('maximum_selections')->label('Maks. pilihan')->numeric()->default(1)->minValue(1),
                        Repeater::make('values')
                            ->label('Pilihan')
                            ->relationship()
                            ->schema([
                                TextInput::make('name')->label('Nama')->placeholder('Less sugar')->required(),
                                TextInput::make('price_adjustment')->label('Tambah harga')->numeric()->default(0)->prefix('Rp'),
                                Toggle::make('is_default')->label('Default'),
                                Toggle::make('is_active')->label('Aktif')->default(true),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->addActionLabel('Tambah pilihan'),
                    ])
                    ->columns(4)
                    ->columnSpanFull()
                    ->defaultItems(0)
                    ->addActionLabel('Tambah kelompok opsi'),
                Repeater::make('recipeIngredients')
                    ->label('Resep / bahan baku (BOM)')
                    ->helperText('Bahan ini akan berkurang otomatis ketika produk terjual.')
                    ->relationship()
                    ->schema([
                        Select::make('ingredient_product_id')->label('Bahan baku')->relationship('ingredient', 'name')->searchable()->preload()->required(),
                        TextInput::make('quantity_required')->label('Jumlah per porsi')->numeric()->integer()->minValue(1)->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->defaultItems(0)
                    ->addActionLabel('Tambah bahan baku'),
            ]);
    }
}
