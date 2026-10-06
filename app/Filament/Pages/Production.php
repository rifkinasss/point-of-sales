<?php

namespace App\Filament\Pages;

use App\Actions\Inventory\RecordProductionAction;
use App\Models\Product;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Production extends Page
{
    protected string $view = 'filament.pages.production';
    protected static ?string $navigationLabel = 'Produksi';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;
    protected static string|UnitEnum|null $navigationGroup = 'Operations';
    protected static ?int $navigationSort = 30;
    public ?int $productId = null; public int $quantity = 1; public ?string $note = null;
    public function getProductsProperty() { return Product::query()->whereHas('recipeIngredients')->with('recipeIngredients.ingredient.unit')->get(); }
    public function produce(): void { $product = Product::query()->findOrFail($this->productId); app(RecordProductionAction::class)->handle($product, $this->quantity, auth()->user(), $this->note); Notification::make()->success()->title('Batch produksi berhasil dicatat.')->send(); $this->reset(['productId', 'note']); $this->quantity = 1; }
    public static function canAccess(): bool { return false; }
}
