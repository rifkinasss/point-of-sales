<?php

namespace App\Filament\Pages;

use App\Actions\Inventory\CompleteStockOpnameAction;
use App\Models\Product;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class StockOpname extends Page
{
    protected string $view = 'filament.pages.stock-opname';
    protected static ?string $navigationLabel = 'Stok opname';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;
    protected static string|UnitEnum|null $navigationGroup = 'Inventori';
    protected static ?int $navigationSort = 20;
    public array $counts = [];
    public ?string $note = null;

    public function mount(): void { $this->counts = Product::query()->with('inventory')->where('is_active', true)->get()->map(fn (Product $p) => ['product_id' => $p->id, 'name' => $p->name, 'system' => $p->inventory?->quantity ?? 0, 'actual_quantity' => $p->inventory?->quantity ?? 0])->all(); }
    public function complete(): void { app(CompleteStockOpnameAction::class)->handle(auth()->user(), $this->counts, $this->note); Notification::make()->success()->title('Stok opname disimpan dan mutasi dibuat.')->send(); $this->mount(); }
    public static function canAccess(): bool { return false; }
}
