<?php

namespace App\Livewire\Owner\Products;

use App\Actions\Inventory\AdjustStockAction;
use App\Actions\Owner\SetProductStatusAction;
use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Queries\OwnerProductQuery;
use App\Services\ActiveStoreContext;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;
    public string $productPublicId = ''; public bool $adjustmentOpen = false; public string $direction = 'in'; public string $quantity = ''; public string $reason = '';
    public function mount(Product|string $product): void { $this->authorizeOwner(); $this->productPublicId = $product instanceof Product ? $product->public_id : $product; abort_unless($this->product, 404); }
    public function toggleStatus(): void { $owner = $this->authorizeOwner(); $product = $this->product; abort_unless($product, 404); app(SetProductStatusAction::class)->execute($owner, $product, ! $product->is_active); }
    public function openAdjustment(): void { abort_unless($this->product?->product_type?->tracksInventory(), 422); $this->resetValidation(); $this->adjustmentOpen = true; }
    public function saveAdjustment(): void { $owner = $this->authorizeOwner(); $data = $this->validate(['direction' => ['required', Rule::in(['in', 'out'])], 'quantity' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:1000']]); $product = $this->product; $store = app(ActiveStoreContext::class)->authorizedStores($owner)->firstWhere('id', $product?->store_id); abort_unless($product && $store, 404); app(AdjustStockAction::class)->handle($product, $owner, $data['direction'] === 'in' ? StockMovementType::AdjustmentIn : StockMovementType::AdjustmentOut, (int) $data['quantity'], trim($data['reason']), $store); $this->reset(['quantity', 'reason']); $this->direction = 'in'; $this->adjustmentOpen = false; session()->flash('status', 'Penyesuaian stok berhasil dicatat.'); }
    public function render() { $this->authorizeOwner(); $product = $this->product; abort_unless($product, 404); $movements = StockMovement::query()->withoutGlobalScopes()->where('store_id', $product->store_id)->where('product_id', $product->id)->latest('occurred_at')->paginate(20); return view('livewire.owner.products.show', compact('product', 'movements'))->layout('components.layouts.owner', ['title' => $product->name, 'activeNav' => 'products']); }
    public function getProductProperty(): ?Product { $owner = $this->authorizeOwner(); return app(OwnerProductQuery::class)->find($owner, $this->productPublicId); }
    private function authorizeOwner(): User { $user = request()->user(); abort_unless($user instanceof User && $user->role === UserRole::Owner && $user->is_active, 403); return $user; }
}
