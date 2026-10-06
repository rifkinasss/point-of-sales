<?php

namespace App\Filament\Resources\Purchases;

use App\Actions\Inventory\ReceivePurchaseAction;
use App\Actions\Inventory\RecordPurchasePaymentAction;
use App\Enums\UserRole;
use App\Filament\Resources\Purchases\Pages\CreatePurchase;
use App\Filament\Resources\Purchases\Pages\EditPurchase;
use App\Filament\Resources\Purchases\Pages\ListPurchases;
use App\Models\Purchase;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PurchaseResource extends Resource
{
    public static function canViewAny(): bool { return false; }
    protected static ?string $model = Purchase::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;
    protected static string|UnitEnum|null $navigationGroup = 'Operations';
    protected static ?string $navigationLabel = 'Pembelian';
    protected static ?int $navigationSort = 20;

    public static function canAccess(): bool { return in_array(auth()->user()?->role, [UserRole::Admin, UserRole::Owner, UserRole::Manager], true); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('supplier_id')->label('Supplier')->relationship('supplier', 'name')->searchable()->preload(),
            TextInput::make('purchase_number')->label('Nomor pembelian')->default(fn () => 'PO-'.now()->format('YmdHis'))->required()->maxLength(50)->unique(ignoreRecord: true),
            Textarea::make('note')->label('Catatan')->columnSpanFull(),
            Repeater::make('items')->relationship()->schema([
                Select::make('product_id')->label('Produk')->relationship('product', 'name')->searchable()->preload()->live()->afterStateUpdated(function ($state, callable $set): void { $product = \App\Models\Product::find($state); if ($product) { $set('product_name', $product->name); $set('unit_cost', $product->cost_price); } })->required(),
                TextInput::make('product_name')->hidden()->dehydrated(),
                TextInput::make('quantity')->label('Jumlah')->numeric()->integer()->minValue(1)->default(1)->required(),
                TextInput::make('unit_cost')->label('Harga satuan')->numeric()->prefix('Rp')->minValue(0)->default(0)->required(),
            ])->columns(3)->columnSpanFull()->defaultItems(1)->addActionLabel('Tambah produk'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('purchase_number')->label('Nomor')->searchable()->sortable(),
            TextColumn::make('supplier.name')->label('Supplier')->placeholder('Tanpa supplier'),
            TextColumn::make('grand_total')->label('Total')->money('IDR', locale: 'id'),
            TextColumn::make('paid_total')->label('Dibayar')->money('IDR', locale: 'id'),
            TextColumn::make('payment_status')->label('Pembayaran')->badge(),
            TextColumn::make('status')->label('Status')->badge(),
            TextColumn::make('received_at')->label('Diterima')->dateTime('d M Y, H:i')->toggleable(),
        ])->defaultSort('created_at', 'desc')->recordActions([
            Action::make('receive')->label('Terima barang')->icon(Heroicon::OutlinedInboxArrowDown)->color('success')->visible(fn (Purchase $record) => $record->status !== 'received')->requiresConfirmation()->action(fn (Purchase $record) => app(ReceivePurchaseAction::class)->handle($record, auth()->user())),
            Action::make('pay')->label('Catat pembayaran')->icon(Heroicon::OutlinedBanknotes)->visible(fn (Purchase $record) => $record->status === 'received' && $record->paid_total < $record->grand_total)->schema([TextInput::make('amount')->label('Nominal')->numeric()->prefix('Rp')->required()->minValue(1), Select::make('method')->label('Metode')->options(['cash'=>'Tunai','bank_transfer'=>'Transfer','qris'=>'QRIS'])->default('cash')->required(), TextInput::make('reference_number')->label('Referensi')->maxLength(100)])->action(fn (Purchase $record, array $data) => app(RecordPurchasePaymentAction::class)->handle($record, auth()->user(), (int) $data['amount'], $data['method'], $data['reference_number'] ?? null)),
            EditAction::make()->visible(fn (Purchase $record) => $record->status !== 'received'),
        ]);
    }

    public static function canEdit(Model $record): bool { return $record->status !== 'received'; }
    public static function getPages(): array { return ['index' => ListPurchases::route('/'), 'create' => CreatePurchase::route('/create'), 'edit' => EditPurchase::route('/{record}/edit')]; }
}
